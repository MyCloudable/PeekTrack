<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the `settings` table with all AI-related feature flags and
 * tunable thresholds.
 *
 * All flags default to the SAFEST possible state for production:
 *   - ai.enabled           FALSE (AI does nothing until opt-in)
 *   - ai.shadow_mode       TRUE  (when enabled, only writes scores,
 *                                 does not change review_state)
 *   - ai.auto_approve_green FALSE
 *   - ai.auto_reject_red    FALSE (NEVER flipped on until after pilot)
 *   - ai.ml_variance_enabled FALSE (Layer 3 off)
 *   - ai.llm_enabled       FALSE (Layer 4 off)
 *
 * Ops flips these on one at a time via the admin UI in Sprint 7+.
 *
 * Run with: php artisan db:seed --class=AiSettingsSeeder
 */
class AiSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // ────────── Master switches ──────────
            ['ai.enabled',              'false', 'bool',  'Master switch. If false, no scoring runs at all.'],
            ['ai.shadow_mode',          'true',  'bool',  'When true, AI writes scores but does not change review_state.'],

            // ────────── Layer enables ──────────
            ['ai.ml_variance_enabled',  'false', 'bool',  'Enable Layer 3 (ML outlier detection). Default off until model is trained.'],
            ['ai.llm_enabled',          'false', 'bool',  'Enable Layer 4 (LLM explanations). Default off until LLM server is online.'],

            // ────────── Band actions ──────────
            ['ai.auto_approve_green',   'false', 'bool',  'When true, Green band auto-sets review_state=ai_approved.'],
            ['ai.auto_reject_red',      'false', 'bool',  'When true, Red band auto-sets review_state=ai_rejected. Leave OFF until client signs off.'],

            // ────────── Band cutoffs ──────────
            ['ai.band_green_max',       '24',    'int',   'Scores 0..band_green_max inclusive are Green.'],
            ['ai.band_yellow_max',      '59',    'int',   'Scores band_green_max+1..band_yellow_max inclusive are Yellow. Above is Red.'],

            // ────────── Reason-code weights ──────────
            ['ai.weight_hard_rule',     '40',    'int',   'Points per hard-rule violation (R1, R2, R3).'],
            ['ai.weight_ratio_out',     '15',    'int',   'Points added when material or equipment ratio is out of band.'],
            ['ai.weight_per_missing_supplier', '3', 'int', 'Points per material row missing supplier. Capped by weight_completeness_cap.'],
            ['ai.weight_per_missing_batch',    '2', 'int', 'Points per material row missing batch. Capped by weight_completeness_cap.'],
            ['ai.weight_completeness_cap', '10', 'int',   'Max total points contributed by completeness issues.'],
            ['ai.weight_estimate_variance', '10','int',   'Points when production_vs_estimate_pct is outside [50%, 120%].'],
            ['ai.weight_ml_outlier',    '15',    'int',   'Points per ML outlier (Layer 3). Applies to material AND equipment separately.'],

            // ────────── Ratio thresholds ──────────
            ['ai.ratio_mat_lower',      '0.5',   'float', 'Material ratio floor, expressed as multiple of median.'],
            ['ai.ratio_mat_upper',      '2.0',   'float', 'Material ratio ceiling, as multiple of median.'],
            ['ai.ratio_equip_lower',    '0.5',   'float', 'Equipment-hours ratio floor.'],
            ['ai.ratio_equip_upper',    '2.0',   'float', 'Equipment-hours ratio ceiling.'],
            ['ai.est_lower_pct',        '0.5',   'float', 'Production >= est_lower_pct * est_total_qty is OK.'],
            ['ai.est_upper_pct',        '1.2',   'float', 'Production <= est_upper_pct * est_total_qty is OK.'],

            // ────────── ML outlier sensitivity ──────────
            ['ai.ml_zscore_threshold',  '3.0',   'float', 'Absolute z-score above which a card is flagged as ML outlier.'],

            // ────────── LLM ──────────
            ['ai.llm_base_url',         'http://localhost:11434', 'string', 'Ollama HTTP endpoint.'],
            ['ai.llm_model_tag',        'llama3.1:8b-instruct-q4_K_M', 'string', 'Ollama model tag.'],
            ['ai.llm_timeout_ms',       '10000', 'int',   'Per-request timeout. If exceeded, fall back to template.'],
            ['ai.llm_max_tokens',       '256',   'int',   'Cap on generated tokens per explanation.'],

            // ────────── ML service ──────────
            ['ai.ml_service_url',       'http://localhost:8001', 'string', 'Python FastAPI variance service.'],
            ['ai.ml_service_timeout_ms','2000',  'int',   'Per-request timeout for ML service.'],
            ['ai.ml_active_model_tag',  '',      'string', 'Populated by model activation workflow. Empty = no model loaded.'],

            // ────────── Audit ──────────
            ['ai.audit_retention_days', '2555',  'int',   '7 years. AI decisions are contract-relevant.'],
        ];

        $now = now();
        foreach ($rows as [$key, $value, $type, $notes]) {
            DB::table('settings')->updateOrInsert(
                ['key_name' => $key],
                [
                    'value'      => $value,
                    'value_type' => $type,
                    'notes'      => $notes,
                    'updated_at' => $now,
                ]
            );
        }

        $this->command->info('Seeded ' . count($rows) . ' AI settings.');
    }
}
