<?php

/**
 * SPRINT 1 TICKET S1-03: Add `review_state` column to jobentries with
 * a typed state machine, backfill from the legacy `approved` int.
 *
 * WHY:
 *   jobentries.approved is int(11) with four observed values:
 *     NULL → pending
 *     1    → approved
 *     2    → rejected
 *     4    → something overflow-related (seen in JobsController)
 *   There is no documented enum. Adding AI states on top of this
 *   unstructured field (ai_approved, ai_flagged, ai_rejected, etc.)
 *   will make the app impossible to reason about.
 *
 *   We add a separate typed column. Old code keeps reading `approved`.
 *   New code reads `review_state`. Both are kept in sync by observers
 *   until `approved` is deprecated in Sprint 8.
 *
 * STATES:
 *     pending_ai       Card submitted, queued for AI scoring
 *     ai_approved      Green band — auto-approved by AI
 *     ai_flagged       Yellow band — queued for human review
 *     ai_rejected      Red band — sent back to superintendent
 *     human_approved   Approved by a reviewer
 *     human_rejected   Rejected by a reviewer
 *     resubmitted      Superintendent fixed and re-sent
 *
 * RISK: None. Adds a column, backfills, adds an index. No data removed.
 *
 * ROLLBACK: Drop the column and index. The legacy `approved` column is
 *           untouched, so the app continues working on that field alone.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('jobentries', function (Blueprint $table) {
            // Placed after `approved` for readability in phpMyAdmin.
            $table->string('review_state', 32)
                ->nullable()
                ->after('approved');

            $table->index('review_state', 'idx_jobentries_review_state');
        });

        // Backfill. Three cases only — anything unexpected stays NULL
        // and will surface in the Sprint 1 data-quality report.
        DB::statement("
            UPDATE jobentries
            SET review_state = CASE
                WHEN submitted = 0                              THEN NULL
                WHEN submitted = 1 AND approved IS NULL         THEN 'pending_ai'
                WHEN submitted = 1 AND approved = 1             THEN 'human_approved'
                WHEN submitted = 1 AND approved = 2             THEN 'human_rejected'
                ELSE NULL
            END
            WHERE review_state IS NULL
        ");

        // Sanity check — log unresolved rows for inspection.
        $unresolved = DB::table('jobentries')
            ->whereNull('review_state')
            ->where('submitted', 1)
            ->count();

        if ($unresolved > 0) {
            // This is expected for the `approved=4` overflow rows.
            // Sprint 1 investigation ticket S1-AUDIT will bucket them.
            \Log::info("[S1-03] {$unresolved} submitted jobentries rows left with NULL review_state (likely legacy approved=4).");
        }
    }

    public function down()
    {
        Schema::table('jobentries', function (Blueprint $table) {
            $table->dropIndex('idx_jobentries_review_state');
            $table->dropColumn('review_state');
        });
    }
};
