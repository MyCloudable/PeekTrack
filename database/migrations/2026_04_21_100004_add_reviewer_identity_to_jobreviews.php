<?php

/**
 * SPRINT 1 TICKET S1-04: Distinguish human vs system reviewers in
 * jobreviews, and link human reviewers back to users.id.
 *
 * WHY:
 *   jobreviews.reviewed_by is varchar(255) — it stores the reviewer's
 *   NAME. When the AI starts writing jobreviews rows (Sprint 5 onward),
 *   we need an unambiguous way to distinguish "approved by Mike Torres"
 *   from "approved by the scoring engine v1.2.3". Putting system tags
 *   in the same name column leads to bugs where filtering by user
 *   accidentally matches AI actions.
 *
 *   We ALSO need to join jobreviews back to users.id for two reasons:
 *     1) To detect reviewer-specific approval biases when training ML
 *        ("Reviewer X approves things others reject" = signal).
 *     2) To reliably look up current user info (email, role, etc.) for
 *        notifications — names change, IDs don't.
 *
 * RISK: None. Additive. Legacy `reviewed_by` name string preserved.
 *
 * BACKFILL: We best-effort match reviewed_by (name) to users.name.
 *           Rows where no unique match is found are left NULL and
 *           reported for manual review.
 *
 * ROLLBACK: Drop the three new columns and their indexes.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('jobreviews', function (Blueprint $table) {
            $table->unsignedBigInteger('reviewed_by_user_id')
                ->nullable()
                ->after('reviewed_by');

            $table->string('reviewed_by_system', 64)
                ->nullable()
                ->after('reviewed_by_user_id')
                ->comment('e.g. ai_scoring_engine_v1. NULL for human reviews.');

            $table->string('decision', 32)
                ->nullable()
                ->after('reviewed_by_system')
                ->comment('approved | rejected | flagged | resubmitted');

            $table->text('decision_reason')
                ->nullable()
                ->after('decision');

            $table->index('reviewed_by_user_id', 'idx_jobreviews_user_id');
            $table->index('reviewed_by_system', 'idx_jobreviews_system');
            $table->index('decision', 'idx_jobreviews_decision');
        });

        // Best-effort backfill. We only populate user_id where there
        // is EXACTLY ONE user with a matching name — ambiguous matches
        // are left NULL to avoid guessing.
        DB::statement("
            UPDATE jobreviews jr
            INNER JOIN (
                SELECT name, MIN(id) AS uid, COUNT(*) AS n
                FROM users
                GROUP BY name
                HAVING n = 1
            ) u ON u.name = jr.reviewed_by
            SET jr.reviewed_by_user_id = u.uid
            WHERE jr.reviewed_by_user_id IS NULL
        ");

        $resolved   = DB::table('jobreviews')->whereNotNull('reviewed_by_user_id')->count();
        $unresolved = DB::table('jobreviews')->whereNull('reviewed_by_user_id')->count();

        \Log::info("[S1-04] Backfilled reviewed_by_user_id: resolved={$resolved}, unresolved={$unresolved}");
    }

    public function down()
    {
        Schema::table('jobreviews', function (Blueprint $table) {
            $table->dropIndex('idx_jobreviews_user_id');
            $table->dropIndex('idx_jobreviews_system');
            $table->dropIndex('idx_jobreviews_decision');
            $table->dropColumn([
                'reviewed_by_user_id',
                'reviewed_by_system',
                'decision',
                'decision_reason',
            ]);
        });
    }
};
