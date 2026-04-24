<?php

/**
 * SPRINT 1 TICKET S1-15: Create `crew_type_ratio_bands` lookup table and
 * verify `users.manager_id` coverage for manager-escalation emails.
 *
 * WHY (from Ron's 2026-04-22 feedback):
 *   1. Paint-behind-paver crews use 2-3 gallons over 6-8 hours. A
 *      flat ratio band flags them daily. We need per-crew-type ratio
 *      bands so the flat rule doesn't false-flag them.
 *
 *   2. Red-card manager escalation emails require us to know each
 *      submitter's manager. PeekTrack already has users.manager_id
 *      (added 2025-03-06) but we have not verified coverage. Without
 *      a manager on file, the escalation silently no-ops, which is
 *      exactly the failure mode Ron asked us to avoid.
 *
 * WHAT THIS MIGRATION DOES:
 *   A. Create crew_type_ratio_bands table. One row per (crew_type_id,
 *      metric). Empty on creation — seeded in Sprint 2 / Sprint 3.
 *   B. Report manager_id coverage via Laravel log. If coverage is
 *      below 80%, the exit criteria for Sprint 1 is not met and ops
 *      must backfill manager assignments before we enable Red
 *      escalation in production.
 *
 * STRUCTURE of crew_type_ratio_bands:
 *   crew_type_id      FK to crew_types.id (existing table).
 *   metric            'material_per_production' | 'equipment_hours_per_production'
 *   lower_bound       Below this, flag S_RATIO_*_LOW
 *   upper_bound       Above this, flag S_RATIO_*_HIGH
 *   source            As in production_qty_limits
 *   active            Soft-disable per (crew_type, metric)
 *
 *   If no row exists for a given (crew_type, metric), the scoring
 *   engine falls back to the global ai.ratio_* settings.
 *
 * ROLLBACK: dropIfExists. Coverage report is advisory, no rollback needed.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // ── A. Create crew_type_ratio_bands ────────────────────────────
        Schema::create('crew_type_ratio_bands', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('crew_type_id');
            $table->enum('metric', [
                'material_per_production',
                'equipment_hours_per_production',
            ]);
            $table->double('lower_bound')->nullable();
            $table->double('upper_bound')->nullable();
            $table->enum('source', [
                'seed_from_history',
                'client_provided',
                'ml_learned',
            ])->default('client_provided');
            $table->boolean('active')->default(true);
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->unique(['crew_type_id', 'metric', 'active'], 'uniq_ctrb_type_metric_active');
            $table->index(['crew_type_id', 'active'], 'idx_ctrb_type_active');
        });

        // ── B. Manager-coverage audit ─────────────────────────────────
        // We only care about active users who submit cards — role 3 = super
        // per existing AddCrewMember.vue filter (roles 3/6/7 are crew).
        // Role 3 is specifically superintendents.
        $coverage = DB::selectOne("
            SELECT
                COUNT(*)                                    AS total,
                SUM(manager_id IS NOT NULL AND manager_id > 0) AS has_manager,
                SUM(manager_id IS NULL OR manager_id = 0)   AS no_manager
            FROM users
            WHERE role_id = 3
        ");

        $pct = $coverage->total > 0
            ? round(100 * $coverage->has_manager / $coverage->total, 1)
            : 0;

        \Log::info(sprintf(
            '[S1-15] Manager coverage for superintendents: %d/%d (%.1f%%)',
            $coverage->has_manager,
            $coverage->total,
            $pct
        ));

        if ($pct < 80 && $coverage->total > 0) {
            \Log::warning(
                "[S1-15] Manager coverage below 80% threshold. " .
                "Red-card escalation emails will silently no-op for " .
                "{$coverage->no_manager} superintendents until " .
                "users.manager_id is populated."
            );
        }
    }

    public function down()
    {
        Schema::dropIfExists('crew_type_ratio_bands');
    }
};
