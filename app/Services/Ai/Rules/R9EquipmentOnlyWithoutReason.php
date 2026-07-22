<?php

namespace App\Services\Ai\Rules;

use App\Models\JobCardAiFeature;
use App\Services\Ai\ValueObjects\RuleFinding;
use Illuminate\Support\Facades\DB;

/**
 * R9: Equipment lines exist, but the card has no production lines,
 * no material lines, and no equipment-only reason.
 *
 * This usually means the crew recorded equipment activity without
 * documenting completed work or explaining why the day was
 * equipment-only.
 *
 * SETTINGS
 *   ai.rule.R9.severity   (default 'medium')
 *
 * Weight is configured separately:
 *   ai.score_weight.R9_EQUIPMENT_ONLY_NO_REASON   (default 30)
 *
 * With the default Yellow threshold of 30, R9 alone produces Yellow.
 */
class R9EquipmentOnlyWithoutReason implements Rule
{
    private string $severity;

    public function __construct(?string $severity = null)
    {
        $this->severity = $severity
            ?? $this->loadStringSetting(
                'ai.rule.R9.severity',
                RuleFinding::SEVERITY_MED
            );
    }

    public function id(): string
    {
        return 'R9';
    }

    public function label(): string
    {
        return 'Equipment-only card has no reason';
    }

    public function check(JobCardAiFeature $feature): array
    {
        $equipmentLineCount = (int) $feature->equipment_line_count;
        $productionLineCount = (int) $feature->production_line_count;
        $materialLineCount = (int) $feature->material_line_count;
        $equipmentOnlyReason = trim(
            (string) ($feature->equipment_only_reason ?? '')
        );

        // R9 requires at least one equipment line.
        if ($equipmentLineCount <= 0) {
            return [];
        }

        // Any production means this is not an equipment-only card.
        if ($productionLineCount > 0) {
            return [];
        }

        // Any material means this is not the exact R9 shape.
        if ($materialLineCount > 0) {
            return [];
        }

        // A supplied reason explains the equipment-only card.
        if ($equipmentOnlyReason !== '') {
            return [];
        }

        return [
            new RuleFinding(
                code: 'R9_EQUIPMENT_ONLY_NO_REASON',
                severity: $this->severity,
                message: 'Card has equipment lines but no production, no material, and no equipment-only reason.',
                ruleId: $this->id(),
                value: (float) $equipmentLineCount,
                context: [
                    'equipment_line_count' => $equipmentLineCount,
                    'production_line_count' => $productionLineCount,
                    'material_line_count' => $materialLineCount,
                    'equipment_only_reason' => null,
                    'configured_severity' => $this->severity,
                ]
            ),
        ];
    }

    private function loadStringSetting(string $key, string $default): string
    {
        $val = DB::table('settings')
            ->where('key_name', $key)
            ->value('value');

        return $val !== null ? (string) $val : $default;
    }
}