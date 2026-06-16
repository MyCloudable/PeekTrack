-- ============================================================================
-- PeekTrack AI — Prospective Agreement Metric (Sprint 4.8)
-- Run weekly during the shadow-measurement window (now → pilot gate).
--
-- WHAT IT MEASURES
--   For cards submitted SINCE the measurement window opened:
--   how does the AI's verdict at submission line up with what humans
--   actually did afterward (rejection events from the jobreviews log)?
--
--   This replaces the unmeasurable "75% agreement with historical
--   rejections" pilot target. Set @window_start to the Sprint 4.8
--   deploy date.
-- ============================================================================

SET @window_start = '2026-06-17';  -- ← set to Sprint 4.8 deploy date

-- ─── 1. Headline: AI verdict vs subsequent human action ────────────────────
SELECT
    asa.band AS ai_verdict_at_submission,
    CASE WHEN rej.link IS NOT NULL THEN 'human rejected at some point'
         ELSE 'never rejected' END AS human_action,
    COUNT(DISTINCT asa.link) AS cards
FROM (
    SELECT link, MAX(id) AS first_audit_id
    FROM ai_scoring_audit
    WHERE created_at >= @window_start
    GROUP BY link
) firstscore
JOIN ai_scoring_audit asa ON asa.id = firstscore.first_audit_id
LEFT JOIN (
    SELECT DISTINCT link
    FROM jobreviews
    WHERE reviewed_by_system IS NULL
      AND decision = 'rejected'
      AND date_reviewed >= @window_start
) rej ON rej.link = asa.link
GROUP BY asa.band, human_action
ORDER BY FIELD(asa.band, 'red', 'yellow', 'green'), human_action;


-- ─── 2. The two pilot-gate numbers ──────────────────────────────────────────
-- Red usefulness: of AI-Red cards, % later rejected by a human
-- Miss rate: of human-rejected cards, % the AI scored Green
SELECT
    ROUND(100.0 *
        COUNT(DISTINCT CASE WHEN asa.band = 'red' AND rej.link IS NOT NULL THEN asa.link END) /
        NULLIF(COUNT(DISTINCT CASE WHEN asa.band = 'red' THEN asa.link END), 0), 1)
        AS red_confirmed_by_human_pct,
    ROUND(100.0 *
        COUNT(DISTINCT CASE WHEN asa.band = 'green' AND rej.link IS NOT NULL THEN asa.link END) /
        NULLIF(COUNT(DISTINCT CASE WHEN rej.link IS NOT NULL THEN asa.link END), 0), 1)
        AS rejected_cards_ai_missed_pct,
    COUNT(DISTINCT CASE WHEN rej.link IS NOT NULL THEN asa.link END) AS total_human_rejections_in_window
FROM (
    SELECT link, MAX(id) AS first_audit_id
    FROM ai_scoring_audit
    WHERE created_at >= @window_start
    GROUP BY link
) firstscore
JOIN ai_scoring_audit asa ON asa.id = firstscore.first_audit_id
LEFT JOIN (
    SELECT DISTINCT link
    FROM jobreviews
    WHERE reviewed_by_system IS NULL
      AND decision = 'rejected'
      AND date_reviewed >= @window_start
) rej ON rej.link = asa.link;


-- ─── 3. Event log health check ──────────────────────────────────────────────
-- Confirms the observer/poller are actually capturing events.
SELECT decision, COUNT(*) AS events,
       MIN(date_reviewed) AS first_event, MAX(date_reviewed) AS latest_event
FROM jobreviews
WHERE reviewed_by_system IS NULL
GROUP BY decision;
