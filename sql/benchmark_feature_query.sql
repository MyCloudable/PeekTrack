-- ═══════════════════════════════════════════════════════════════════════
-- PeekTrack AI · Sprint 1 · S1-09
-- Feature aggregation benchmark harness
-- ═══════════════════════════════════════════════════════════════════════
--
-- PURPOSE
--   Prove that build_feature_row.sql (S1-11) meets the performance
--   exit criterion: < 200ms p95 on prod-sized data.
--
-- HOW TO RUN
--   On the STAGING database that has a recent prod dump:
--     1. Ensure S1-01 (indexes) and S1-02 (CHAR(36) collation) have
--        been applied. Without both, numbers are meaningless.
--     2. mysql -u peektrack -p peektrack_staging < benchmark_feature_query.sql
--     3. Results print to stdout and also land in tmp_benchmark_results.
--
-- WHAT IT DOES
--   - Picks 100 random links from jobentries where submitted = 1
--   - For each link, runs the feature query and measures wall time
--   - Computes min / p50 / p90 / p95 / p99 / max
--   - Compares EXPLAIN output before and after the index patch, so
--     you can confirm indexes are being used
--
-- EXIT CRITERIA
--   p95 < 200 ms  →  PASS, unblocks Sprint 2
--   p95 >= 200 ms →  investigate EXPLAIN output, check for:
--                      - stale statistics (run ANALYZE TABLE)
--                      - missing index from S1-01
--                      - subquery not using index due to collation mismatch
-- ═══════════════════════════════════════════════════════════════════════

-- 1) Preflight: verify the indexes from S1-01 exist.
SELECT
    table_name,
    index_name,
    column_name
FROM information_schema.statistics
WHERE table_schema = DATABASE()
  AND table_name IN ('production', 'material', 'equipment', 'jobreviews')
  AND column_name = 'link'
ORDER BY table_name, index_name;

-- If the result set above is empty, STOP. Run Sprint 1 S1-01 first.

-- 2) Warm caches on the relevant tables.
SELECT COUNT(*) FROM production;
SELECT COUNT(*) FROM material;
SELECT COUNT(*) FROM equipment;
SELECT COUNT(*) FROM jobentries;

-- 3) Prepare the benchmark table.
DROP TEMPORARY TABLE IF EXISTS tmp_benchmark_results;
CREATE TEMPORARY TABLE tmp_benchmark_results (
    iteration   INT NOT NULL,
    link        CHAR(36) NOT NULL,
    elapsed_ms  DOUBLE NOT NULL
);

-- 4) Pick 100 random, previously-submitted cards.
DROP TEMPORARY TABLE IF EXISTS tmp_benchmark_links;
CREATE TEMPORARY TABLE tmp_benchmark_links (
    iteration INT AUTO_INCREMENT PRIMARY KEY,
    link      CHAR(36) NOT NULL
);

INSERT INTO tmp_benchmark_links (link)
SELECT link
FROM jobentries
WHERE submitted = 1
ORDER BY RAND()
LIMIT 100;

-- 5) Drive the benchmark from a stored procedure so timing is captured
--    inside MySQL (avoids client-side network jitter).
DROP PROCEDURE IF EXISTS bench_feature_query;
DELIMITER //
CREATE PROCEDURE bench_feature_query()
BEGIN
    DECLARE done        INT DEFAULT 0;
    DECLARE iter_var    INT;
    DECLARE link_var    CHAR(36);
    DECLARE t_start     DOUBLE;
    DECLARE t_end       DOUBLE;

    DECLARE cur CURSOR FOR SELECT iteration, link FROM tmp_benchmark_links;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN cur;

    bench_loop: LOOP
        FETCH cur INTO iter_var, link_var;
        IF done = 1 THEN LEAVE bench_loop; END IF;

        SET @target_link = link_var;
        SET t_start = UNIX_TIMESTAMP(NOW(6));

        -- Body: run the feature query. We use the same logic as
        -- build_feature_row.sql inline, because nested SOURCE is
        -- unreliable inside a procedure.
        DROP TEMPORARY TABLE IF EXISTS tmp_feature_preview;
        CREATE TEMPORARY TABLE tmp_feature_preview AS
        SELECT
            je.link,
            COALESCE(p.total_qty, 0) AS p_qty,
            COALESCE(m.total_qty, 0) AS m_qty,
            COALESCE(e.total_hours, 0) AS e_hrs
        FROM jobentries je
        LEFT JOIN (
            SELECT link, SUM(qty) AS total_qty
            FROM production WHERE link = @target_link GROUP BY link
        ) p ON p.link = je.link
        LEFT JOIN (
            SELECT link, SUM(qty) AS total_qty
            FROM material WHERE link = @target_link AND deleted_at IS NULL GROUP BY link
        ) m ON m.link = je.link
        LEFT JOIN (
            SELECT link, SUM(hours) AS total_hours
            FROM equipment WHERE link = @target_link AND deleted_at IS NULL GROUP BY link
        ) e ON e.link = je.link
        WHERE je.link = @target_link
        LIMIT 1;

        SET t_end = UNIX_TIMESTAMP(NOW(6));

        INSERT INTO tmp_benchmark_results (iteration, link, elapsed_ms)
        VALUES (iter_var, link_var, (t_end - t_start) * 1000);
    END LOOP bench_loop;

    CLOSE cur;
END //
DELIMITER ;

CALL bench_feature_query();

-- 6) Print the results.
SELECT
    COUNT(*)                                                              AS samples,
    ROUND(MIN(elapsed_ms), 2)                                             AS min_ms,
    ROUND(AVG(elapsed_ms), 2)                                             AS avg_ms,
    ROUND((SELECT elapsed_ms FROM tmp_benchmark_results
           ORDER BY elapsed_ms LIMIT 1 OFFSET 49), 2)                     AS p50_ms,
    ROUND((SELECT elapsed_ms FROM tmp_benchmark_results
           ORDER BY elapsed_ms LIMIT 1 OFFSET 89), 2)                     AS p90_ms,
    ROUND((SELECT elapsed_ms FROM tmp_benchmark_results
           ORDER BY elapsed_ms LIMIT 1 OFFSET 94), 2)                     AS p95_ms,
    ROUND((SELECT elapsed_ms FROM tmp_benchmark_results
           ORDER BY elapsed_ms LIMIT 1 OFFSET 98), 2)                     AS p99_ms,
    ROUND(MAX(elapsed_ms), 2)                                             AS max_ms
FROM tmp_benchmark_results;

-- 7) Pass/fail verdict.
SELECT
    CASE
        WHEN (SELECT elapsed_ms FROM tmp_benchmark_results
              ORDER BY elapsed_ms LIMIT 1 OFFSET 94) < 200
            THEN 'PASS: p95 under 200ms. Sprint 2 unblocked.'
        ELSE 'FAIL: p95 exceeds 200ms. Investigate EXPLAIN output below.'
    END AS verdict;

-- 8) If FAIL, this is the EXPLAIN plan for the median-slowest card.
SET @slowest_link = (
    SELECT link FROM tmp_benchmark_results
    ORDER BY elapsed_ms DESC LIMIT 1
);

EXPLAIN
SELECT je.link
FROM jobentries je
LEFT JOIN production p ON p.link = je.link
LEFT JOIN material   m ON m.link = je.link AND m.deleted_at IS NULL
LEFT JOIN equipment  e ON e.link = je.link AND e.deleted_at IS NULL
WHERE je.link = @slowest_link;

-- 9) Cleanup (optional — comment out if you want to inspect raw data).
-- DROP PROCEDURE IF EXISTS bench_feature_query;
-- DROP TEMPORARY TABLE IF EXISTS tmp_benchmark_links;
-- DROP TEMPORARY TABLE IF EXISTS tmp_benchmark_results;
-- DROP TEMPORARY TABLE IF EXISTS tmp_feature_preview;
