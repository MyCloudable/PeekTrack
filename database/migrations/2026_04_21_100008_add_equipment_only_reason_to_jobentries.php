<?php

/**
 * SPRINT 1 TICKET S1-12: Add equipment-only reason code to jobentries.
 *
 * WHY (from Ron's 2026-04-22 feedback):
 *   Equipment-only cards are valid (crew on site, no production logged)
 *   but today there is no structured record of WHY there was no
 *   production. This causes downstream questions from billing. Ron
 *   asked for a required dropdown on submit: rain / breakdown / etc.
 *
 *   Having this as a structured enum (not free text) lets the AI and
 *   reviewers pattern-match quickly, and lets ops run reports like
 *   "how many rain days did Longline have in March".
 *
 * STRUCTURE:
 *   equipment_only_reason       ENUM of canned reasons OR 'other'
 *   equipment_only_reason_text  Free text, required when reason='other'
 *
 *   Both are NULL when production_total_qty > 0 (i.e. normal production
 *   cards don't need a reason). The app enforces non-null when
 *   production is zero AND equipment_total_hours > 0.
 *
 * NOTE: We deliberately do NOT add a CHECK constraint in the DB
 *       because validation belongs in the application layer, where it
 *       can surface actionable error messages. The DB is the fallback.
 *
 * RISK: None. Additive columns, all nullable.
 *
 * ROLLBACK: dropColumn on both.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('jobentries', function (Blueprint $table) {
            $table->enum('equipment_only_reason', [
                'rain',
                'truck_breakdown',
                'job_cancelled',
                'scheduling',
                'safety_stand_down',
                'other',
            ])->nullable()->after('review_state');

            $table->string('equipment_only_reason_text', 500)
                ->nullable()
                ->after('equipment_only_reason')
                ->comment("Free text. Required when equipment_only_reason = 'other'.");

            $table->index('equipment_only_reason', 'idx_jobentries_eq_reason');
        });
    }

    public function down()
    {
        Schema::table('jobentries', function (Blueprint $table) {
            $table->dropIndex('idx_jobentries_eq_reason');
            $table->dropColumn(['equipment_only_reason', 'equipment_only_reason_text']);
        });
    }
};
