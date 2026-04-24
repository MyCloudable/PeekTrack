<?php

/**
 * SPRINT 1 TICKET S1-16: Kickback workflow columns.
 *
 * WHY (from Ron's 2026-04-22 feedback + Brad's 2026-04-22 clarification):
 *   Red cards actually DO kick back to the superintendent for correction.
 *   This migration adds the data shape that tracks:
 *     - when a card was kicked back
 *     - how many times it has been kicked back
 *     - when the super was notified
 *     - when (if ever) the manager was auto-escalated
 *
 *   Business rules driven off these columns (implemented in Sprint 3):
 *     - review_state 'kicked_back_to_super' is the state after a Red score
 *     - Super gets email + in-app notification on kickback
 *     - After 3 days outstanding, ONE auto-escalation email goes to manager
 *     - Second kickback on the same card routes to the reviewer queue
 *       (review_state 'ai_flagged') instead of kicking back again
 *     - Managers and reviewers can override-approve at any point
 *
 * DEPENDENCIES: S1-03 already added review_state to jobentries. This
 *               migration extends that column's allowed values.
 *
 * RISK: Low. Adding VARCHAR values does not break existing rows. New
 *       columns are nullable.
 *
 * ROLLBACK: Drop columns. The review_state VARCHAR(32) accepts any
 *           string, so no constraint rollback needed.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('jobentries', function (Blueprint $table) {
            // Timestamp of most recent kickback. Used by the dashboard
            // to compute age-in-days and by the escalation job to find
            // cards past the 3-day threshold.
            $table->timestamp('kicked_back_at')
                ->nullable();
                // ->after('ai_override_at');

            // How many times THIS card has been kicked back. Scoring
            // engine uses this to decide whether another Red should
            // kick back again or route to the reviewer queue.
            $table->unsignedTinyInteger('kickback_count')
                ->default(0)
                ->after('kicked_back_at');

            // When the submitting super was notified. Populated by the
            // notification dispatcher. NULL means notification pending
            // or failed — monitor this for delivery issues.
            $table->timestamp('super_notified_at')
                ->nullable()
                ->after('kickback_count');

            // When (if ever) the 3-day auto-escalation fired. NULL means
            // either under 3 days old OR already resolved. Prevents
            // duplicate escalation emails if the scheduled job runs
            // multiple times for the same card.
            $table->timestamp('manager_escalated_at')
                ->nullable()
                ->after('super_notified_at');

            // Index supports the manager dashboard query "all outstanding
            // kickbacks for my supers, sorted by oldest first" and the
            // escalation scheduler query "cards > 3 days old without
            // manager_escalated_at".
            $table->index(
                ['review_state', 'kicked_back_at'],
                'idx_jobentries_kickback_state_age'
            );
        });
    }

    public function down()
    {
        Schema::table('jobentries', function (Blueprint $table) {
            $table->dropIndex('idx_jobentries_kickback_state_age');
            $table->dropColumn([
                'kicked_back_at',
                'kickback_count',
                'super_notified_at',
                'manager_escalated_at',
            ]);
        });
    }
};
