<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model for the `jobcard_ai_features` table created in Sprint 2
 * (S2-01 create_jobcard_ai_features_table). One row per
 * job card, refreshed on every submit and resubmit. This is the input to
 * all four AI layers and the source of truth for "what did the AI see
 * when it scored this card?"
 *
 * KEY RELATIONSHIPS
 *   $feature->jobEntry      — the originating job card (via 'link' UUID)
 *   JobEntry::$aiFeature    — inverse on JobEntry model (add separately)
 *
 * USAGE
 *   $feature = JobCardAiFeature::where('link', $link)->first();
 *   $feature = app(FeatureBuilder::class)->buildOrRefresh($link);
 */
class JobCardAiFeature extends Model
{
    protected $table = 'jobcard_ai_features';

    protected $fillable = [
        'link',
        'job_number',
        'workdate',
        'submitted_by_user_id',
        'crew_type_id',
        // Production
        'production_line_count',
        'production_total_qty',
        'production_distinct_phases',
        'production_distinct_descs',
        // Material
        'material_line_count',
        'material_total_qty',
        'material_missing_supplier_cnt',
        'material_missing_batch_cnt',
        'material_distinct_suppliers',
        // Equipment
        'equipment_line_count',
        'equipment_total_hours',
        'equipment_distinct_trucks',
        // Ratios
        'material_per_production',
        'equipment_hours_per_production',
        // Estimate context
        'est_total_qty',
        'production_vs_estimate_pct',
        // v1.1 additions
        'has_unestimated_items',
        'unestimated_line_count',
        'has_complexity_override',
        'qty_exceeds_soft_ceiling',
        'qty_exceeds_hard_ceiling',
        'pair_mismatch_required_count',
        'pair_mismatch_recommended_cnt',
        // Historical context
        'prior_cards_same_job',
        'prior_cards_same_user_30d',
        'user_prior_rejection_rate',
        'user_30d_avg_material_qty',
        'user_30d_avg_equipment_hours',
        // Flags
        'has_hard_rule_violation',
        // Equipment-only context
        'equipment_only_reason',
        'equipment_only_reason_text',
        'notes_length',
        // Audit
        'feature_version',
    ];

    protected $casts = [
        'workdate'                       => 'date',
        'computed_at'                    => 'datetime',
        'production_total_qty'           => 'float',
        'material_total_qty'             => 'float',
        'equipment_total_hours'          => 'float',
        'material_per_production'        => 'float',
        'equipment_hours_per_production' => 'float',
        'est_total_qty'                  => 'float',
        'production_vs_estimate_pct'     => 'float',
        'user_prior_rejection_rate'      => 'float',
        'user_30d_avg_material_qty'      => 'float',
        'user_30d_avg_equipment_hours'   => 'float',
        'has_unestimated_items'          => 'bool',
        'has_complexity_override'        => 'bool',
        'qty_exceeds_soft_ceiling'       => 'bool',
        'qty_exceeds_hard_ceiling'       => 'bool',
        'has_hard_rule_violation'        => 'bool',
    ];

    public $timestamps = false; // The table uses `computed_at` not Laravel timestamps

    /**
     * Scope: only feature rows that pass the training-set criteria.
     * Used by ML training data prep in Sprint 5.
     */
    public function scopeForTraining($query)
    {
        return $query
            ->where('has_hard_rule_violation', 0)
            ->whereNotNull('production_total_qty')
            ->whereDate('workdate', '>=', now()->subYears(2));
    }

    /**
     * Scope: feature rows for a given user in a recent window.
     * Used by the FeatureBuilder for prior_cards_same_user_30d.
     */
    public function scopeForUserRecent($query, int $userId, int $days = 30)
    {
        return $query
            ->where('submitted_by_user_id', $userId)
            ->whereDate('workdate', '>=', now()->subDays($days));
    }
}
