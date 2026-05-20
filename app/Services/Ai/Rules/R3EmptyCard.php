<?php

namespace App\Services\Ai\Rules;

use App\Models\JobCardAiFeature;
use App\Services\Ai\ValueObjects\RuleFinding;
use Illuminate\Support\Facades\DB;

/**
 * R3: Card with no production, material, OR equipment, AND no equipment_only_reason.
 *
 * v1 (Sprint 3): hard rule, any empty card without a reason = Red.
 * v2 (Sprint 4.5): severity configurable.
 *
 * Calibration logic: R3 is narrow — it only fires when the card is literally
 * empty AND the super hasn't filled in why. We DON'T loosen the threshold
 * (an empty card with no reason really is broken), but if the data shows
 * humans approve some of these because the reason is captured elsewhere
 * (e.g., in job_notes), severity should drop.
 *
 * SETTINGS
 *   ai.rule.R3.severity   (default 'hard')
 */
class R3EmptyCard implements Rule
{
    private string $severity;

    public function __construct(?string $severity = null)
    {
        $this->severity = $severity ?? $this->loadStringSetting('ai.rule.R3.severity', RuleFinding::SEVERITY_HARD);
    }

    public function id(): string { return 'R3'; }
    public function label(): string { return 'Card has no production, material, or equipment'; }

    public function check(JobCardAiFeature $feature): array
    {
        $isEmpty = $feature->production_total_qty == 0
                && $feature->material_total_qty == 0
                && $feature->equipment_total_hours == 0;

        if (!$isEmpty) return [];

        // Equipment-only reason set => crew was there but didn't do work. Skip.
        if (!empty($feature->equipment_only_reason)) return [];

        return [
            new RuleFinding(
                code: 'R3_EMPTY_CARD',
                severity: $this->severity,
                message: 'Card has no production, material, or equipment lines. Add work or mark as equipment-only with a reason.',
                ruleId: $this->id(),
                context: [
                    'production_total_qty'  => 0,
                    'material_total_qty'    => 0,
                    'equipment_total_hours' => 0,
                    'configured_severity'   => $this->severity,
                ]
            ),
        ];
    }

    private function loadStringSetting(string $key, string $default): string
    {
        $val = DB::table('settings')->where('key_name', $key)->value('value');
        return $val !== null ? (string) $val : $default;
    }
}
