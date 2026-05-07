<?php

namespace App\Services\Ai\Rules;

use App\Models\JobCardAiFeature;
use App\Services\Ai\ValueObjects\RuleFinding;

/**
 * R1: Material was used but no production was logged.
 *
 * Hard rule. A card with material qty > 0 but production qty = 0 means
 * the crew used materials without producing anything. Either:
 *   (a) data entry error — they forgot to enter the production
 *   (b) genuine waste — paint cleanup, prep, etc.
 *
 * v1: always Red. Super has to either add the production lines or
 * mark it as equipment-only with reason 'other' + explanation.
 */
class R1MaterialWithoutProduction implements Rule
{
    public function id(): string { return 'R1'; }
    public function label(): string { return 'Material logged without production'; }

    public function check(JobCardAiFeature $feature): array
    {
        if ($feature->material_total_qty > 0 && $feature->production_total_qty == 0) {
            return [
                new RuleFinding(
                    code: 'R1_MATERIAL_NO_PRODUCTION',
                    severity: RuleFinding::SEVERITY_HARD,
                    message: sprintf(
                        'Card has %s material qty but 0 production. Add production lines or mark as equipment-only.',
                        rtrim(rtrim(number_format($feature->material_total_qty, 2), '0'), '.')
                    ),
                    ruleId: $this->id(),
                    value: $feature->material_total_qty,
                    threshold: 0,
                    context: [
                        'material_total_qty'   => $feature->material_total_qty,
                        'production_total_qty' => $feature->production_total_qty,
                    ]
                ),
            ];
        }
        return [];
    }
}
