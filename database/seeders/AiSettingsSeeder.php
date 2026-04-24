<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the `settings` table with all AI-related feature flags and
 * tunable thresholds.
 *
 * v1.1 — 2026-04-22
 *   Added flags derived from Ron Cross's operational feedback:
 *     - ai.rule_check_notes_for_material_no_prod
 *     - ai.auto_flag_unestimated
 *     - ai.auto_flag_tape_grinding
 *     - ai.manager_escalation_enabled
 *     - ai.manager_escalation_in_app
 *     - ai.typo_multiplier_threshold
 *     - ai.ratio_bands_mode
 *     - ai.complexity_override_patterns
 *
 * All AI-related flags default to the SAFEST possible state:
 *   - ai.enabled           false  (AI does nothing until opt-in)
 *   - ai.shadow_mode       true   (scores but does not change state)
 *   - ai.auto_approve_green false
 *   - ai.auto_reject_red   false  (NEVER flip on until after pilot)
 *
 * Ops flips these via the admin UI during Sprint 7+.
 *
 * Run:  php artisan db:seed --class=AiSettingsSeeder
 */
class AiSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // ═══════════════ Master switches ═══════════════
            ['ai.enabled',                                  'false', 'bool',   'Master switch. If false, no scoring runs at all.'],
            ['ai.shadow_mode',                              'true',  'bool',   'When true, AI writes scores but does not change review_state.'],

            // ═══════════════ Layer enables ═══════════════
            ['ai.ml_variance_enabled',                      'false', 'bool',   'Enable Layer 3 (ML outlier detection). Default off until model is trained.'],
            ['ai.llm_enabled',                              'false', 'bool',   'Enable Layer 4 (LLM explanations). Default off until LLM server is online.'],

            // ═══════════════ Band actions ═══════════════
            ['ai.auto_approve_green',                       'false', 'bool',   'When true, Green band auto-sets review_state=ai_approved.'],
            ['ai.auto_reject_red',                          'false', 'bool',   'When true, Red band auto-sets review_state=ai_rejected. Leave OFF until client signs off.'],

            // ═══════════════ Band cutoffs ═══════════════
            ['ai.band_green_max',                           '24',    'int',    'Scores 0..band_green_max inclusive are Green.'],
            ['ai.band_yellow_max',                          '59',    'int',    'Scores band_green_max+1..band_yellow_max inclusive are Yellow.'],

            // ═══════════════ Reason-code weights ═══════════════
            ['ai.weight_hard_rule',                         '40',    'int',    'Points per hard-rule violation (R1, R2, R3).'],
            ['ai.weight_ratio_out',                         '15',    'int',    'Points for ratio out of band.'],
            ['ai.weight_per_missing_supplier',              '3',     'int',    'Points per material row missing supplier.'],
            ['ai.weight_per_missing_batch',                 '2',     'int',    'Points per material row missing batch.'],
            ['ai.weight_completeness_cap',                  '10',    'int',    'Max total points from completeness issues.'],
            ['ai.weight_estimate_variance',                 '10',    'int',    'Points when production is outside [50%, 120%] of estimate.'],
            ['ai.weight_ml_outlier',                        '15',    'int',    'Points per ML outlier.'],
            ['ai.weight_unestimated_item',                  '12',    'int',    'Points when any line item has no corresponding job_data row.'],
            ['ai.weight_pair_mismatch_required',            '15',    'int',    'Points when a REQUIRED production/material pair is mismatched.'],
            ['ai.weight_pair_mismatch_recommended',         '5',     'int',    'Points when a RECOMMENDED pair is mismatched.'],
            ['ai.weight_qty_soft_ceiling',                  '15',    'int',    'Points when qty exceeds production_qty_limits.soft_max.'],
            ['ai.weight_user_avg_typo',                     '12',    'int',    'Points when qty is typo_multiplier_threshold above user 30d avg.'],
            ['ai.weight_complexity_override',               '0',     'int',    'Points for COMPLEXITY_OVERRIDE. 0 keeps card Green-score-wise; it still force-flags.'],
            ['ai.weight_equipment_only_no_reason',          '25',    'int',    'Points when equipment-only card has no reason code.'],

            // ═══════════════ Ratio thresholds (global fallback) ═══════════════
            ['ai.ratio_mat_lower',                          '0.5',   'float',  'Global floor: material_per_production, multiple of median.'],
            ['ai.ratio_mat_upper',                          '2.0',   'float',  'Global ceiling: material_per_production.'],
            ['ai.ratio_equip_lower',                        '0.5',   'float',  'Global floor: equipment_hours_per_production.'],
            ['ai.ratio_equip_upper',                        '2.0',   'float',  'Global ceiling: equipment_hours_per_production.'],
            ['ai.est_lower_pct',                            '0.5',   'float',  'Production >= est_lower_pct * est_total_qty is OK.'],
            ['ai.est_upper_pct',                            '1.2',   'float',  'Production <= est_upper_pct * est_total_qty is OK.'],
            ['ai.ratio_bands_mode',                         'per_type', 'string', "'global' or 'per_type'. per_type uses crew_type_ratio_bands with global fallback."],

            // ═══════════════ Rule-softening (client feedback) ═══════════════
            ['ai.rule_check_notes_for_material_no_prod',    'true',  'bool',   "If true, R1 only fires when notes field is ALSO empty. Handles Longline island-border case."],
            ['ai.notes_min_length_for_rule_skip',           '10',    'int',    'Notes must have at least this many chars (trimmed) to satisfy the R1 check.'],

            // ═══════════════ Auto-flag triggers ═══════════════
            ['ai.auto_flag_unestimated',                    'true',  'bool',   'Any line item without a matching job_data row auto-flags the card.'],
            ['ai.auto_flag_tape_grinding',                  'true',  'bool',   'Tape and grinding jobs auto-flag until ML has learned them.'],
            ['ai.complexity_override_patterns',             '["tape","grinding","grind","mill"]', 'json', 'Production description patterns that trigger COMPLEXITY_OVERRIDE (auto-Yellow).'],

            // ═══════════════ Typo detection ═══════════════
            ['ai.typo_multiplier_threshold',                '10.0',  'float',  'Flag when qty is >= this multiple of the user\'s 30-day average for that item.'],
            ['ai.user_avg_min_samples',                     '3',     'int',    'Min number of prior cards required before user-average typo check runs.'],

            // ═══════════════ Manager escalation (3-day trigger, not immediate) ═══════════════
            ['ai.manager_escalation_enabled',               'true',  'bool',   'Send escalation email to manager when a kicked-back card sits past ai.kickback_auto_escalate_days.'],
            ['ai.manager_escalation_in_app',                'true',  'bool',   'Also create an in-app notification for the manager at the same threshold.'],
            ['ai.manager_escalation_email_subject',         'Overdue kickback: {superintendent_name} has {days_overdue}-day card {job_number}', 'string', 'Subject template. Placeholders: {superintendent_name}, {days_overdue}, {job_number}, {risk_score}'],

            // ═══════════════ Estimating-queue routing (v1.3) ═══════════════
            ['ai.auto_route_to_estimating',                 'false', 'bool',   'When true, cards with R8 UNESTIMATED_ITEM auto-route to review_state=pending_estimating. Default OFF during pilot; reviewer confirms routing. Flip to true at rollout only if pilot validates.'],
            ['ai.suggest_estimating_routing',               'true',  'bool',   'When true and AI detects unestimated items but auto-route is off, show "AI recommends estimating queue" badge in reviewer UI for one-click routing.'],
            ['ai.weight_unestimated_item',                  '12',    'int',    'Points added per card with unestimated items (R8). Also force-flags to Yellow. Overrides the generic weight value — see also the v1.1 key.'],
            ['ai.unestimated_phase_codes',                  '["98-09000","98-19999"]', 'json', 'Phase codes in production/material tables that indicate unestimated items. Per client 2026-04-22. If billing adds more codes, update this list AND the SQL in FeatureBuilder (both are checked).'],

            // ═══════════════ Red-card routing & kickback workflow ═══════════════
            ['ai.red_routing_mode',                         'back_to_super', 'string', "'back_to_super' (default, per client 2026-04-22) or 'reviewer_queue'. Back-to-super is the primary workflow."],
            ['ai.kickback_auto_escalate_days',              '3',     'int',    'Days a kicked-back card can sit before an escalation email fires to the manager. Single email, not recurring.'],
            ['ai.kickback_max_attempts_before_review',      '1',     'int',    'After this many kickback attempts, the card routes to the reviewer queue instead of kicking back again. Default 1 = second Red goes to reviewers.'],
            ['ai.kickback_notification_subject',            'Action required: job card {job_number} needs correction', 'string', 'Email subject to super when card is kicked back.'],
            ['ai.kickback_escalation_subject',              'Overdue: {superintendent_name} has {days_overdue}-day kicked-back card {job_number}', 'string', 'Email subject to manager at 3-day escalation.'],

            // ═══════════════ LLM ═══════════════
            ['ai.llm_base_url',                             'http://localhost:11434', 'string', 'Ollama HTTP endpoint.'],
            ['ai.llm_model_tag',                            'llama3.1:8b-instruct-q4_K_M', 'string', 'Ollama model tag.'],
            ['ai.llm_timeout_ms',                           '10000', 'int',    'Per-request timeout. Fall back to template if exceeded.'],
            ['ai.llm_max_tokens',                           '256',   'int',    'Cap on generated tokens per explanation.'],

            // ═══════════════ ML service ═══════════════
            ['ai.ml_service_url',                           'http://localhost:8001', 'string', 'Python FastAPI variance service.'],
            ['ai.ml_service_timeout_ms',                    '2000',  'int',    'Per-request timeout for ML service.'],
            ['ai.ml_service_active_model_tag',              '',      'string', 'Active model tag. Empty = no model loaded.'],
            ['ai.ml_zscore_threshold',                      '3.0',   'float',  'Abs z-score above which a card is flagged as ML outlier.'],

            // ═══════════════ Audit ═══════════════
            ['ai.audit_retention_days',                     '2555',  'int',    '7 years. AI decisions are contract-relevant.'],

            // ═══════════════ OCR (explicitly scoped out for v1) ═══════════════
            ['ai.ocr_enabled',                              'false', 'bool',   'OCR (green book, road lists) is NOT in v1. Flag left here as placeholder for v2.'],
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
