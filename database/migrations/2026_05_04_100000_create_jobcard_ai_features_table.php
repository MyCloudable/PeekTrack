<?php

/**
 * SPRINT 2 TICKET S2-01: Create jobcard_ai_features table.
 *
 * BACKGROUND
 *   This table was specified in §4.1 of the v2.0 tech spec but missed
 *   from the Sprint 1 migration set. Without it, FeatureBuilder,
 *   RefreshAiFeatureRowJob, and BackfillAiFeatures all fail at runtime.
 *
 *   This migration MUST run before any Sprint 2 code is exercised on
 *   staging. Add to the deployment sequence right after the Sprint 1
 *   migrations (S1-01 through S1-18) and before the Sprint 2 code is
 *   deployed.
 *
 * WHAT THIS TABLE IS
 *   One row per submitted job card, refreshed on every submit and
 *   resubmit. Holds the canonical feature representation that all four
 *   AI layers consume. Also serves as the audit record of "what did
 *   the AI see when it scored this card?"
 *
 * RELATIONSHIPS
 *   link → jobentries.link (UUID, 1:1)
 *   crew_type_id → crew_types.id (nullable; not all cards have a crew type)
 *   submitted_by_user_id → users.id
 *
 * SIZING
 *   At ~50K-200K cards over 2 years, this table will be 50K-200K rows
 *   after backfill. Each row is ~250 bytes; total ~50MB. No partitioning
 *   needed.
 *
 * INDEXES
 *   uniq_link        — primary lookup key (one row per card)
 *   idx_job_number   — for "all features for job J24-1234"
 *   idx_workdate     — for date-range queries (training set, 30d windows)
 *   idx_submitted_by — for user-history queries (30d averages, rejection rate)
 *   idx_crew_type    — for per-crew-type analysis and ML training stratification
 *
 * RISK: None. New table. No data migration required.
 * ROLLBACK: dropIfExists.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('jobcard_ai_features', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Identity (joins to source data)
            $table->char('link', 36);
            $table->string('job_number', 255);
            $table->date('workdate');
            $table->unsignedInteger('submitted_by_user_id');
            $table->unsignedBigInteger('crew_type_id')->nullable();

            // ── Production aggregates ──
            $table->integer('production_line_count')->default(0);
            $table->double('production_total_qty')->default(0);
            $table->integer('production_distinct_phases')->default(0);
            $table->integer('production_distinct_descs')->default(0);

            // ── Material aggregates ──
            $table->integer('material_line_count')->default(0);
            $table->double('material_total_qty')->default(0);
            $table->integer('material_missing_supplier_cnt')->default(0);
            $table->integer('material_missing_batch_cnt')->default(0);
            $table->integer('material_distinct_suppliers')->default(0);

            // ── Equipment aggregates ──
            $table->integer('equipment_line_count')->default(0);
            $table->double('equipment_total_hours')->default(0);
            $table->integer('equipment_distinct_trucks')->default(0);

            // ── Ratios (NULL when denominator would be 0) ──
            $table->double('material_per_production')->nullable();
            $table->double('equipment_hours_per_production')->nullable();

            // ── Estimate context ──
            $table->double('est_total_qty')->nullable();
            $table->double('production_vs_estimate_pct')->nullable();

            // ── v1.1 / v1.3 additions ──
            $table->boolean('has_unestimated_items')->default(false);
            $table->integer('unestimated_line_count')->default(0);
            $table->boolean('has_complexity_override')->default(false);
            $table->boolean('qty_exceeds_soft_ceiling')->default(false);
            $table->boolean('qty_exceeds_hard_ceiling')->default(false);
            $table->integer('pair_mismatch_required_count')->default(0);
            $table->integer('pair_mismatch_recommended_cnt')->default(0);

            // ── Historical context (per-user rolling stats) ──
            $table->integer('prior_cards_same_job')->default(0);
            $table->integer('prior_cards_same_user_30d')->default(0);
            $table->double('user_prior_rejection_rate')->nullable();
            $table->double('user_30d_avg_material_qty')->nullable();
            $table->double('user_30d_avg_equipment_hours')->nullable();

            // ── Hard-rule precompute (for ML training filter) ──
            $table->boolean('has_hard_rule_violation')->default(false);

            // ── Equipment-only context ──
            $table->string('equipment_only_reason', 32)->nullable();
            $table->string('equipment_only_reason_text', 500)->nullable();
            $table->integer('notes_length')->default(0);

            // ── Audit ──
            $table->timestamp('computed_at')->useCurrent()->useCurrentOnUpdate();
            $table->string('feature_version', 16)->default('v1');

            // ── Indexes ──
            $table->unique('link', 'uniq_link');
            $table->index('job_number', 'idx_job_number');
            $table->index('workdate', 'idx_workdate');
            $table->index('submitted_by_user_id', 'idx_submitted_by');
            $table->index('crew_type_id', 'idx_crew_type');
        });
    }

    public function down()
    {
        Schema::dropIfExists('jobcard_ai_features');
    }
};
