-- ═══════════════════════════════════════════════════════════════════════
-- PeekTrack AI · Sprint 1 · S1-11
-- Feature aggregation SQL
-- ═══════════════════════════════════════════════════════════════════════
--
-- PURPOSE
--   This is the canonical SQL that builds one row in jobcard_ai_features
--   for a given jobentries.link. It is what the Laravel FeatureBuilder
--   service will wrap in Sprint 2. Here it lives as a standalone file so
--   the DBA and the backend team can:
--     - benchmark it against prod-sized data before writing code
--     - inspect what values the AI will actually score against
--     - use it as a reference for the backfill script in Sprint 2
--
-- USAGE
--   Single-card run (development):
--     SET @target_link = '550e8400-e29b-41d4-a716-446655440000';
--     SOURCE build_feature_row.sql;
--     SELECT * FROM tmp_feature_preview;
--
--   Benchmark (see sql/benchmark_feature_query.sql):
--     -- See comments in that file for wiring.
--
-- PRE-REQUISITES
--   Sprint 1 S1-01 (indexes) must be deployed. Without them this query
--   will table-scan and benchmark numbers will be meaningless.
--
-- NOTES ON NULL HANDLING
--   We LEFT JOIN to each line-item aggregation subquery. A card with no
--   material rows at all produces no row in the `m` subquery, so all
--   material_* features end up 0 via COALESCE. Same for production and
--   equipment. This is intentional — "no material" is a valid state and
--   must scored as 0, not NULL.
--
--   Ratios are the ONLY nullable features. A ratio is NULL when its
--   denominator is 0 — because "divide by zero" has no sane default
--   and the scoring engine treats NULL ratios specially (they don't
--   trigger the out-of-band reason code, they fall through to the
--   hard-rule check).
-- ═══════════════════════════════════════════════════════════════════════

-- Clean slate for preview runs
DROP TEMPORARY TABLE IF EXISTS tmp_feature_preview;

CREATE TEMPORARY TABLE tmp_feature_preview AS
SELECT
    je.link                                              AS link,
    je.job_number                                        AS job_number,
    je.workdate                                          AS workdate,
    je.userId                                            AS submitted_by_user_id,

    -- ─────── Production aggregates ───────
    COALESCE(p.line_count, 0)                            AS production_line_count,
    COALESCE(p.total_qty, 0)                             AS production_total_qty,
    COALESCE(p.phases, 0)                                AS production_distinct_phases,
    COALESCE(p.descs, 0)                                 AS production_distinct_descs,

    -- ─────── Material aggregates ───────
    COALESCE(m.line_count, 0)                            AS material_line_count,
    COALESCE(m.total_qty, 0)                             AS material_total_qty,
    COALESCE(m.miss_supplier, 0)                         AS material_missing_supplier_cnt,
    COALESCE(m.miss_batch, 0)                            AS material_missing_batch_cnt,
    COALESCE(m.distinct_suppliers, 0)                    AS material_distinct_suppliers,

    -- ─────── Equipment aggregates ───────
    COALESCE(e.line_count, 0)                            AS equipment_line_count,
    COALESCE(e.total_hours, 0)                           AS equipment_total_hours,
    COALESCE(e.distinct_trucks, 0)                       AS equipment_distinct_trucks,

    -- ─────── Ratios (NULL when denominator is zero) ───────
    CASE
        WHEN COALESCE(p.total_qty, 0) > 0
            THEN COALESCE(m.total_qty, 0) / p.total_qty
        ELSE NULL
    END                                                  AS material_per_production,

    CASE
        WHEN COALESCE(p.total_qty, 0) > 0
            THEN COALESCE(e.total_hours, 0) / p.total_qty
        ELSE NULL
    END                                                  AS equipment_hours_per_production,

    -- ─────── Estimate context (from job_data) ───────
    jd.est_total_qty                                     AS est_total_qty,

    CASE
        WHEN jd.est_total_qty IS NOT NULL
         AND jd.est_total_qty > 0
         AND COALESCE(p.total_qty, 0) > 0
            THEN (p.total_qty / jd.est_total_qty) * 100.0
        ELSE NULL
    END                                                  AS production_vs_estimate_pct,

    -- ─────── Historical context ───────
    COALESCE(hist_job.n, 0)                              AS prior_cards_same_job,
    COALESCE(hist_user.n, 0)                             AS prior_cards_same_user_30d,
    hist_user.rejection_rate                             AS user_prior_rejection_rate,

    -- ─────── Hard-rule violation flag (precomputed for fast filtering) ───────
    CASE
        WHEN COALESCE(m.total_qty, 0) > 0
         AND COALESCE(p.total_qty, 0) = 0           THEN 1   -- R1: material w/o production
        WHEN COALESCE(p.total_qty, 0) > 0
         AND COALESCE(e.total_hours, 0) = 0         THEN 1   -- R2: production w/o equipment
        WHEN COALESCE(p.total_qty, 0) = 0
         AND COALESCE(m.total_qty, 0) = 0
         AND COALESCE(e.total_hours, 0) = 0         THEN 1   -- R3: empty card
        ELSE 0
    END                                                  AS has_hard_rule_violation,

    NOW()                                                AS computed_at,
    'v1'                                                 AS feature_version

FROM jobentries je

-- ─────── Production subquery ───────
LEFT JOIN (
    SELECT
        link,
        COUNT(*)                    AS line_count,
        SUM(qty)                    AS total_qty,
        COUNT(DISTINCT phase)       AS phases,
        COUNT(DISTINCT description) AS descs
    FROM production
    WHERE link = @target_link
    GROUP BY link
) p ON p.link = je.link

-- ─────── Material subquery (canonical table: `material`, not `materials`) ───────
LEFT JOIN (
    SELECT
        link,
        COUNT(*)                                  AS line_count,
        SUM(qty)                                  AS total_qty,
        SUM(CASE WHEN supplier IS NULL OR supplier = '' THEN 1 ELSE 0 END) AS miss_supplier,
        SUM(CASE WHEN batch    IS NULL OR batch    = '' THEN 1 ELSE 0 END) AS miss_batch,
        COUNT(DISTINCT NULLIF(supplier, ''))      AS distinct_suppliers
    FROM material
    WHERE link = @target_link
      AND deleted_at IS NULL
    GROUP BY link
) m ON m.link = je.link

-- ─────── Equipment subquery ───────
LEFT JOIN (
    SELECT
        link,
        COUNT(*)              AS line_count,
        SUM(hours)            AS total_hours,
        COUNT(DISTINCT truck) AS distinct_trucks
    FROM equipment
    WHERE link = @target_link
      AND deleted_at IS NULL
    GROUP BY link
) e ON e.link = je.link

-- ─────── Job-estimate subquery ───────
-- job_data has one row per (job_number, phase, description, unit).
-- For v1 we sum all est_qty rows for the job, which is a coarse proxy
-- for "how much work is this job supposed to be". Sprint 2 can refine
-- this to match on phase/description if the client wants finer grain.
LEFT JOIN (
    SELECT
        job_number,
        SUM(est_qty) AS est_total_qty
    FROM job_data
    WHERE job_number = (SELECT job_number FROM jobentries WHERE link = @target_link LIMIT 1)
    GROUP BY job_number
) jd ON jd.job_number = je.job_number

-- ─────── Prior cards on same job ───────
LEFT JOIN (
    SELECT
        job_number,
        COUNT(*) AS n
    FROM jobentries
    WHERE job_number = (SELECT job_number FROM jobentries WHERE link = @target_link LIMIT 1)
      AND submitted = 1
      AND link <> @target_link
    GROUP BY job_number
) hist_job ON hist_job.job_number = je.job_number

-- ─────── Submitting user's recent history (last 30 days) ───────
LEFT JOIN (
    SELECT
        userId,
        COUNT(*) AS n,
        SUM(CASE WHEN review_state IN ('human_rejected', 'ai_rejected') THEN 1 ELSE 0 END)
            / NULLIF(COUNT(*), 0) AS rejection_rate
    FROM jobentries
    WHERE userId = (SELECT userId FROM jobentries WHERE link = @target_link LIMIT 1)
      AND submitted = 1
      AND link <> @target_link
      AND workdate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY userId
) hist_user ON hist_user.userId = je.userId

WHERE je.link = @target_link
LIMIT 1;
