<?php

namespace App\Services\Ai\Rules;

use App\Models\JobCardAiFeature;
use App\Services\Ai\ValueObjects\RuleFinding;

/**
 * R3: Card with no production, material, OR equipment.
 *
 * Hard rule UNLESS equipment_only_reason is set (e.g. 'rain' = crew
 * showed up but rained out). Equipment-only cards with a valid reason
 * code skip this check.
 *
 * If the reason is 'other', the super must also fill in
 * equipment_only_reason_text — that's enforced at submit time, not here.
 *
 * Pure-empty cards (no equipment-only reason set) hit Red.
 */
class R3EmptyCard implements Rule
{
    public function id(): string { return 'R3'; }
    public function label(): string { return 'Card has no production, material, or equipment'; }

    public function check(JobCardAiFeature $feature): array
    {
        $isEmpty = $feature->production_total_qty == 0
                && $feature->material_total_qty == 0
                && $feature->equipment_total_hours == 0;

        if (!$isEmpty) {
            return [];
        }

        // Equipment-only reason set => crew was there but didn't do work.
        // Skip the rule (this card is legitimately empty by design).
        if (!empty($feature->equipment_only_reason)) {
            return [];
        }

        return [
            new RuleFinding(
                code: 'R3_EMPTY_CARD',
                severity: RuleFinding::SEVERITY_HARD,
                message: 'Card has no production, material, or equipment lines. Add work or mark as equipment-only with a reason.',
                ruleId: $this->id(),
                context: [
                    'production_total_qty'  => 0,
                    'material_total_qty'    => 0,
                    'equipment_total_hours' => 0,
                ]
            ),
        ];
    }
}
