<?php

namespace App\Services\Ai\Rules;

use App\Models\JobCardAiFeature;
use App\Services\Ai\ValueObjects\RuleFinding;

/**
 * R2: Production was logged but no equipment hours.
 *
 * Hard rule. Production work requires equipment (a paint truck, a
 * thermo machine, an arrowboard). Zero equipment hours with non-zero
 * production is almost always a data entry error.
 *
 * Edge case: hand-applied tape can have very low equipment hours
 * (just a vehicle to drive to the site). The rule fires only on
 * EXACTLY zero equipment, not low equipment. Low ratios are caught
 * by R7 (ratio bands).
 */
class R2ProductionWithoutEquipment implements Rule
{
    public function id(): string { return 'R2'; }
    public function label(): string { return 'Production logged without equipment hours'; }

    public function check(JobCardAiFeature $feature): array
    {
        if ($feature->production_total_qty > 0 && $feature->equipment_total_hours == 0) {
            return [
                new RuleFinding(
                    code: 'R2_PRODUCTION_NO_EQUIPMENT',
                    severity: RuleFinding::SEVERITY_HARD,
                    message: sprintf(
                        'Card has %s production qty but 0 equipment hours. Add equipment lines.',
                        rtrim(rtrim(number_format($feature->production_total_qty, 2), '0'), '.')
                    ),
                    ruleId: $this->id(),
                    value: $feature->equipment_total_hours,
                    threshold: 0,
                    context: [
                        'production_total_qty'  => $feature->production_total_qty,
                        'equipment_total_hours' => $feature->equipment_total_hours,
                    ]
                ),
            ];
        }
        return [];
    }
}
