<?php

namespace App\Services\Ai\Rules;

use App\Models\JobCardAiFeature;
use App\Services\Ai\ValueObjects\RuleFinding;

/**
 * R8: Card has unestimated items.
 *
 * Detection happens in Sprint 2's UnestimatedItemDetector (phase codes
 * 98-09000 / 98-19999). Result lands on the feature row as
 * `has_unestimated_items` and `unestimated_line_count`. This rule
 * just reads the flag.
 *
 * Severity: SEVERITY_HIGH (Layer 2). Routes to estimating queue if the
 * `ai.auto_route_to_estimating` setting is true (Sprint 4 wiring).
 *
 * Why HIGH and not HARD:
 *   Unestimated items are a workflow signal, not a wrongness signal.
 *   The card data may be perfect; it just needs an estimator to
 *   add unit prices before billing. Auto-Red would be wrong.
 *
 * Yellow with this code lets the manager dashboard route correctly.
 */
class R8UnestimatedItems implements Rule
{
    public function id(): string { return 'R8'; }
    public function label(): string { return 'Card has unestimated production/material items'; }

    public function check(JobCardAiFeature $feature): array
    {
        if (!$feature->has_unestimated_items) return [];

        return [
            new RuleFinding(
                code: 'R8_UNESTIMATED_ITEMS',
                severity: RuleFinding::SEVERITY_HIGH,
                message: sprintf(
                    'Card has %d unestimated line item%s. Route to estimating queue.',
                    $feature->unestimated_line_count,
                    $feature->unestimated_line_count === 1 ? '' : 's'
                ),
                ruleId: $this->id(),
                value: (float) $feature->unestimated_line_count,
                context: [
                    'unestimated_line_count' => $feature->unestimated_line_count,
                ]
            ),
        ];
    }
}
