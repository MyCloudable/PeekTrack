<?php

/**
 * SPRINT 1 TICKET S1-18: Backfill `pending_estimating` review_state for
 * the estimating queue (approved = 4 legacy rows).
 *
 * WHY (from client clarification 2026-04-22):
 *   `approved = 4` is an ACTIVE operational queue, not a dead state.
 *   It's the estimating queue: when a reviewer sees a card with
 *   unestimated items, they manually set approved = 4 to route it to
 *   the estimating/billing team who create the estimate entries, then
 *   flip the card back to "submitted" so a reviewer can approve it.
 *
 *   The original S1-03 backfill left these rows with review_state = NULL
 *   because we didn't know what the state meant. This migration fixes
 *   that.
 *
 * NEW STATE: 'pending_estimating'
 *   Meaning: Card routed to the estimating team to create estimates for
 *            unestimated items. Billing/estimating team processes, then
 *            flips approved back to NULL (via existing workflow) which
 *            rescores the card through the AI pipeline.
 *
 * NOTE ON estimate_completed:
 *   We DELIBERATELY do not add a persistent `estimate_completed` enum
 *   value. Per client workflow, completion means the card returns to
 *   the submitted/pending-AI flow. No new state needed — the existing
 *   review_state = 'pending_ai' covers it when approved is flipped
 *   back to NULL.
 *
 * WHAT THIS MIGRATION DOES:
 *   1. Backfills review_state = 'pending_estimating' on all rows
 *      where submitted = 1 AND approved = 4 AND review_state IS NULL
 *   2. Adds an audit log entry with the count for visibility.
 *
 * DEPENDENCIES: S1-03 already added the review_state column.
 *
 * RISK: None. Pure UPDATE on a non-indexed state column. The WHERE
 *       clause is narrow — only targets NULL review_state rows that
 *       are known to be in the estimating queue.
 *
 * ROLLBACK: The down() method clears review_state back to NULL for
 *           the affected rows. approved = 4 (the legacy source of
 *           truth) is untouched.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        $affected = DB::update("
            UPDATE jobentries
            SET review_state = 'pending_estimating'
            WHERE submitted = 1
              AND approved = 4
              AND review_state IS NULL
        ");

        \Log::info("[S1-18] Backfilled {$affected} rows to review_state='pending_estimating' (the estimating queue).");

        // Sanity check: there should be no more submitted rows with
        // approved=4 and NULL review_state after this migration.
        $remaining = DB::table('jobentries')
            ->where('submitted', 1)
            ->where('approved', 4)
            ->whereNull('review_state')
            ->count();

        if ($remaining > 0) {
            \Log::warning("[S1-18] {$remaining} rows still have approved=4 and NULL review_state. Investigate.");
        } else {
            \Log::info("[S1-18] Estimating queue backfill complete. No residual NULL review_states with approved=4.");
        }
    }

    public function down()
    {
        DB::update("
            UPDATE jobentries
            SET review_state = NULL
            WHERE review_state = 'pending_estimating'
              AND approved = 4
              AND submitted = 1
        ");
    }
};
