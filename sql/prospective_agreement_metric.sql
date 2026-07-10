-- ============================================================================
-- PeekTrack AI — Prospective Agreement Metric v2 (2026-07-10)
-- Run weekly against peektrack_ai_test (any day; the window is fixed).
--
-- WHAT IT MEASURES
--   For cards worked since the measurement window opened: how does the AI's
--   FIRST verdict line up with what humans actually did (rejection events
--   from the jobreviews log)?
--
-- WHY v2
--   v1 windowed the card population by ai_scoring_audit.created_at. That
--   column is reset by every truncate + re-score (the standard calibration
--   workflow), so "the window" silently widened to all ~62K historical cards
--   and the metric could only ever read zero. v2 anchors on the card itself
--   (jobentries.workdate) and the rejection log (date_reviewed) — neither of
--   which the AI pipeline can reset.
--
--   v2 also reports its own blind spot. Rejected cards are frequently
--   hard-deleted in prod (delete-and-reenter is the dominant crew response
--   to a kickback; `link` never changes for the life of a row, so a vanished
--   link means deletion). A rejection whose card was never scored before
--   deletion is unmeasurable — it is COUNTED here, not hidden.
--
--   Depends on the BackfillAiFeatures eligibility fix (same commit): kicked-
--   back cards (submitted=0/approved=2) are now scored on first sight.
--   Without it, sections 2-3 read ~zero.
--
--   Baseline captures (date_reviewed 2026-06-17) are excluded by design —
--   they are stale kickbacks predating the window, kept as ML labels only.
-- ============================================================================

SET @window_start = '2026-06-18';  -- fixed; never move between weekly reads


-- ─── 1. Headline: first AI verdict vs subsequent human action ───────────────
--     Population: cards WORKED since the window opened that entered review.
--     'never scored' should shrink to just the newest arrivals once the
--     eligibility fix is live — if it grows, the pipeline is lagging.
SELECT
    COALESCE(fb.band, 'never scored') AS ai_first_verdict,
    CASE WHEN rej.link IS NOT NULL THEN 'human rejected'
         ELSE 'not rejected' END      AS human_action,
    COUNT(DISTINCT je.link)           AS cards
FROM jobentries je
LEFT JOIN (
    SELECT a.link, a.band
    FROM ai_scoring_audit a
    JOIN (SELECT link, MIN(id) AS first_id FROM ai_scoring_audit GROUP BY link) f
      ON f.first_id = a.id
) fb ON fb.link = je.link
LEFT JOIN (
    SELECT DISTINCT link FROM jobreviews
    WHERE reviewed_by_system IS NULL AND decision = 'rejected'
      AND date_reviewed >= @window_start
) rej ON rej.link = je.link
WHERE je.workdate >= @window_start
  AND (je.submitted = 1 OR je.approved IS NOT NULL OR je.kicked_back_at IS NOT NULL)
GROUP BY ai_first_verdict, human_action
ORDER BY FIELD(ai_first_verdict, 'red', 'yellow', 'green', 'never scored'),
         human_action;


-- ─── 2. Gate number #1 — Red usefulness ─────────────────────────────────────
--     Of in-window cards whose FIRST verdict was Red: % a human rejected.
--     Rejected-then-DELETED reds drop out of this denominator (conservative);
--     the deletion loss is quantified in section 3. Expect small n — read the
--     count, not just the percentage.
SELECT
    COUNT(DISTINCT je.link)  AS red_cards_in_window,
    COUNT(DISTINCT rej.link) AS red_rejected_by_human,
    ROUND(100.0 * COUNT(DISTINCT rej.link) / NULLIF(COUNT(DISTINCT je.link), 0), 1)
        AS red_confirmed_by_human_pct
FROM jobentries je
JOIN (
    SELECT a.link, a.band
    FROM ai_scoring_audit a
    JOIN (SELECT link, MIN(id) AS first_id FROM ai_scoring_audit GROUP BY link) f
      ON f.first_id = a.id
) fb ON fb.link = je.link AND fb.band = 'red'
LEFT JOIN (
    SELECT DISTINCT link FROM jobreviews
    WHERE reviewed_by_system IS NULL AND decision = 'rejected'
      AND date_reviewed >= @window_start
) rej ON rej.link = je.link
WHERE je.workdate >= @window_start
  AND (je.submitted = 1 OR je.approved IS NOT NULL OR je.kicked_back_at IS NOT NULL);


-- ─── 3. Gate number #2 — Miss rate, plus the honesty numbers ────────────────
--     Base: every human rejection captured in-window. The log is AI-owned and
--     survives card deletion, so this base is complete even when jobentries
--     rows vanish. missed = first verdict was Green (of the scored ones).
--     unmeasurable = never scored before the card disappeared.
SELECT
    COUNT(DISTINCT r.link) AS rejections_in_window,
    COUNT(DISTINCT CASE WHEN fb.band IS NOT NULL THEN r.link END)
        AS rejections_with_ai_verdict,
    ROUND(100.0 * COUNT(DISTINCT CASE WHEN fb.band = 'green' THEN r.link END)
        / NULLIF(COUNT(DISTINCT CASE WHEN fb.band IS NOT NULL THEN r.link END), 0), 1)
        AS rejected_cards_ai_missed_pct,
    COUNT(DISTINCT CASE WHEN fb.band IS NULL THEN r.link END)
        AS unmeasurable_rejections,
    ROUND(100.0 * COUNT(DISTINCT CASE WHEN fb.band IS NULL THEN r.link END)
        / NULLIF(COUNT(DISTINCT r.link), 0), 1)
        AS unmeasurable_pct
FROM (
    SELECT DISTINCT link FROM jobreviews
    WHERE reviewed_by_system IS NULL AND decision = 'rejected'
      AND date_reviewed >= @window_start
) r
LEFT JOIN (
    SELECT a.link, a.band
    FROM ai_scoring_audit a
    JOIN (SELECT link, MIN(id) AS first_id FROM ai_scoring_audit GROUP BY link) f
      ON f.first_id = a.id
) fb ON fb.link = r.link;


-- ─── 4. What happens to rejected cards (in-window) ──────────────────────────
--     Tracks the delete-and-reenter habit week over week. 'card deleted' rows
--     are permanent metric + ML-label losses unless scored before deletion —
--     the eligibility fix exists to win that race.
SELECT
    CASE WHEN je.link IS NULL THEN 'card deleted from jobentries'
         WHEN je.approved = 2 THEN 'still kicked back'
         WHEN je.approved = 1 THEN 'corrected & re-approved (same link)'
         ELSE 'other' END AS outcome,
    COUNT(DISTINCT r.link) AS cards
FROM (
    SELECT DISTINCT link FROM jobreviews
    WHERE reviewed_by_system IS NULL AND decision = 'rejected'
      AND date_reviewed >= @window_start
) r
LEFT JOIN jobentries je ON je.link = r.link
GROUP BY outcome
ORDER BY cards DESC;


-- ─── 5. Event log health check ──────────────────────────────────────────────
--     Confirms the observer/poller are actually capturing events.
--     latest_event should be within ~24h of the run.
SELECT decision, COUNT(*) AS events,
       MIN(date_reviewed) AS first_event, MAX(date_reviewed) AS latest_event
FROM jobreviews
WHERE reviewed_by_system IS NULL
GROUP BY decision;
