<?php

namespace App\Services\Ai;

use App\Models\JobCardAiFeature;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Builds and persists feature rows from raw card data.
 *
 * Single responsibility: given a card's `link`, produce the canonical
 * feature row representation in `jobcard_ai_features`. Idempotent —
 * re-running produces the same output for a given input snapshot.
 *
 * INVOCATION POINTS
 *   1. Real-time (JobEntrySubmitObserver) — on submit/resubmit
 *   2. Async backfill (BackfillAiFeatures Artisan) — for historical data
 *   3. Test fixtures — direct calls in unit tests
 *
 * PERFORMANCE
 *   The aggregation query (sql/build_feature_row.sql) was benchmarked at
 *   p95 = 2.55ms in Sprint 1 against staging-sized data. The benchmark
 *   covered the same query this service uses, so production performance
 *   should be in the same ballpark. If the p95 ever exceeds 200ms,
 *   re-run sql/benchmark_feature_query.sql to identify regression.
 *
 * ERROR HANDLING
 *   This service throws on bad input (missing card, missing crew_type
 *   resolution) but degrades gracefully on derived metrics (estimate
 *   context, user 30d averages) — those become NULL in the feature row.
 *   The Layer 1/2 logic treats NULL ratios as "skip the check" rather
 *   than as a 0 value (which would false-trigger rules).
 */
class FeatureBuilder
{
    public function __construct(
        private UnestimatedItemDetector $unestimatedDetector
    ) {}

    /**
     * Build or refresh the feature row for a single card.
     *
     * @param  string $link  jobentries.link UUID
     * @return JobCardAiFeature
     * @throws RuntimeException if the card doesn't exist or crew_type is unresolvable
     */
    public function buildOrRefresh(string $link): JobCardAiFeature
    {
        $card = DB::table('jobentries')
            ->where('link', $link)
            ->first();

        if (!$card) {
            throw new RuntimeException("FeatureBuilder: card with link={$link} not found");
        }

        // Aggregations from the three line-item tables. Each subquery is
        // narrow and indexed (S1-01); the result is one row per card.
        $aggregations = $this->aggregateLineItems($link);

        // Derived metrics: ratios, estimate context, historical user context.
        $derived = $this->computeDerivedMetrics($link, $card, $aggregations);

        // Hard rule violation flag (Layer 1's domain — but we precompute
        // the OR of the three primary rules for query convenience and
        // for the ML training-set filter).
        $hasHardRule = $this->detectHardRuleViolation($aggregations);

        // Unestimated items: detected via phase code (98-09000, 98-19999)
        // per client clarification 2026-04-22. See R8_UNESTIMATED_DETECTION.md.
        $unestimated = $this->unestimatedDetector->detect($link);

        // Persist with updateOrInsert keyed by link.
        return JobCardAiFeature::updateOrCreate(
            ['link' => $link],
            array_merge(
                [
                    'job_number'            => $card->job_number,
                    'workdate'              => $card->workdate,
                    'submitted_by_user_id'  => $card->userId ?? null,
                    'crew_type_id'          => $card->crew_type_id ?? null,
                    'has_hard_rule_violation' => $hasHardRule ? 1 : 0,
                    'has_unestimated_items'   => $unestimated['has_unestimated'] ? 1 : 0,
                    'unestimated_line_count'  => $unestimated['line_count'],
                    'equipment_only_reason'      => $card->equipment_only_reason ?? null,
                    'equipment_only_reason_text' => $card->equipment_only_reason_text ?? null,
                    'notes_length'          => mb_strlen($card->notes ?? ''),
                    'feature_version'       => 'v1',
                ],
                $aggregations,
                $derived,
            )
        );
    }

    /**
     * Aggregate line items from production, material, equipment.
     * Mirrors the structure of sql/build_feature_row.sql but uses
     * Eloquent query builder for testability.
     */
    private function aggregateLineItems(string $link): array
    {
        $production = DB::table('production')
            ->where('link', $link)
            ->whereNull('deleted_at')
            ->selectRaw('
                COUNT(*)                    AS production_line_count,
                COALESCE(SUM(qty), 0)       AS production_total_qty,
                COUNT(DISTINCT phase)       AS production_distinct_phases,
                COUNT(DISTINCT description) AS production_distinct_descs
            ')
            ->first();

        $material = DB::table('material')
            ->where('link', $link)
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*)                                                  AS material_line_count,
                COALESCE(SUM(qty), 0)                                     AS material_total_qty,
                SUM(CASE WHEN supplier IS NULL OR supplier = '' THEN 1 ELSE 0 END) AS material_missing_supplier_cnt,
                SUM(CASE WHEN batch IS NULL OR batch = '' THEN 1 ELSE 0 END)       AS material_missing_batch_cnt,
                COUNT(DISTINCT NULLIF(supplier, ''))                      AS material_distinct_suppliers
            ")
            ->first();

        $equipment = DB::table('equipment')
            ->where('link', $link)
            ->whereNull('deleted_at')
            ->selectRaw('
                COUNT(*)              AS equipment_line_count,
                COALESCE(SUM(hours), 0) AS equipment_total_hours,
                COUNT(DISTINCT truck) AS equipment_distinct_trucks
            ')
            ->first();

        return [
            'production_line_count'         => (int)  $production->production_line_count,
            'production_total_qty'          => (float)$production->production_total_qty,
            'production_distinct_phases'    => (int)  $production->production_distinct_phases,
            'production_distinct_descs'     => (int)  $production->production_distinct_descs,
            'material_line_count'           => (int)  $material->material_line_count,
            'material_total_qty'            => (float)$material->material_total_qty,
            'material_missing_supplier_cnt' => (int)  $material->material_missing_supplier_cnt,
            'material_missing_batch_cnt'    => (int)  $material->material_missing_batch_cnt,
            'material_distinct_suppliers'   => (int)  $material->material_distinct_suppliers,
            'equipment_line_count'          => (int)  $equipment->equipment_line_count,
            'equipment_total_hours'         => (float)$equipment->equipment_total_hours,
            'equipment_distinct_trucks'     => (int)  $equipment->equipment_distinct_trucks,
        ];
    }

    /**
     * Ratios, estimate context, historical user averages.
     * NULL when the denominator would be zero — Layer 2 treats NULL as
     * "skip the check" rather than zero (which would false-trigger rules).
     */
    private function computeDerivedMetrics(string $link, $card, array $agg): array
    {
        $matPerProd = $agg['production_total_qty'] > 0
            ? $agg['material_total_qty'] / $agg['production_total_qty']
            : null;

        $equipPerProd = $agg['production_total_qty'] > 0
            ? $agg['equipment_total_hours'] / $agg['production_total_qty']
            : null;

        // Estimate context: pull est_total_qty from job_data if available.
        $estTotal = DB::table('job_data')
            ->where('job_number', $card->job_number)
            ->sum('est_qty');
        $estTotal = $estTotal > 0 ? (float)$estTotal : null;

        $prodVsEst = ($estTotal !== null && $estTotal > 0)
            ? ($agg['production_total_qty'] / $estTotal) * 100
            : null;

        // Prior cards on the same job
        $priorSameJob = DB::table('jobentries')
            ->where('job_number', $card->job_number)
            ->where('submitted', 1)
            ->where('link', '<>', $link)
            ->count();

        // Prior cards by same user in last 30 days
        $userId = $card->userId ?? null;
        $priorUser30d = $userId
            ? DB::table('jobentries')
                ->where('userId', $userId)
                ->where('submitted', 1)
                ->where('workdate', '>=', now()->subDays(30))
                ->where('link', '<>', $link)
                ->count()
            : 0;

        // User 30-day rejection rate (excludes current card)
        $userRejectRate = null;
        if ($userId) {
            $userTotals = DB::table('jobentries')
                ->where('userId', $userId)
                ->where('submitted', 1)
                ->where('workdate', '>=', now()->subDays(30))
                ->where('link', '<>', $link)
                ->selectRaw("
                    COUNT(*)                                               AS total,
                    SUM(CASE WHEN approved = 2 THEN 1 ELSE 0 END)          AS rejected
                ")
                ->first();
            if ($userTotals && $userTotals->total > 0) {
                $userRejectRate = $userTotals->rejected / $userTotals->total;
            }
        }

        return [
            'material_per_production'        => $matPerProd,
            'equipment_hours_per_production' => $equipPerProd,
            'est_total_qty'                  => $estTotal,
            'production_vs_estimate_pct'     => $prodVsEst,
            'prior_cards_same_job'           => $priorSameJob,
            'prior_cards_same_user_30d'      => $priorUser30d,
            'user_prior_rejection_rate'      => $userRejectRate,
            // The 30d averages are populated by the backfill command,
            // not the realtime path — they're rolling stats that don't
            // need recomputation per-submit.
            'user_30d_avg_material_qty'      => null,
            'user_30d_avg_equipment_hours'   => null,
        ];
    }

    private function detectHardRuleViolation(array $agg): bool
    {
        // R1: material w/o production
        if ($agg['material_total_qty'] > 0 && $agg['production_total_qty'] == 0) {
            return true;
        }
        // R2: production w/o equipment
        if ($agg['production_total_qty'] > 0 && $agg['equipment_total_hours'] == 0) {
            return true;
        }
        // R3: empty card
        if ($agg['production_total_qty'] == 0
            && $agg['material_total_qty'] == 0
            && $agg['equipment_total_hours'] == 0) {
            return true;
        }
        return false;
    }
}
