<?php

namespace App\Services\Ai\Rules;

use App\Models\JobCardAiFeature;
use App\Services\Ai\ValueObjects\RuleFinding;
use Illuminate\Support\Facades\DB;

/**
 * R2: Production was logged but minimal/no equipment hours.
 *
 * v1 (Sprint 3): hard rule, any production with zero equipment = Red.
 * v2 (Sprint 4.5): configurable thresholds + severity.
 *
 * Calibration showed v1 was overcalling — small production qty with low
 * equipment hours is common for hand-applied tape or end-of-day touchups.
 * The new defaults look for: significant production AND essentially-zero
 * equipment, which is a stronger signal of data entry error.
 *
 * SETTINGS
 *   ai.rule.R2.min_production_qty   (default 0)
 *     Only fire if production_total_qty > this value
 *
 *   ai.rule.R2.max_equipment_hours  (default 0)
 *     Fire if equipment_total_hours <= this value
 *     (Default 0 means "exactly zero hours")
 *
 *   ai.rule.R2.severity             (default 'hard')
 *     One of: 'hard' | 'high' | 'medium' | 'low'
 *
 * EXAMPLES
 *   Defaults (matches Sprint 3 behavior): "any production with exactly zero
 *   equipment is HARD"
 *
 *   Tuned (post-calibration): min_production_qty=50, max_equipment_hours=0.5,
 *   severity=high → "production qty >50 with <0.5 equipment hours is a
 *   strong soft-rule signal worth a Yellow"
 */
class R2ProductionWithoutEquipment implements Rule
{
    private float $minProdQty;
    private float $maxEquipHours;
    private string $severity;

    public function __construct(
        ?float $minProdQty = null,
        ?float $maxEquipHours = null,
        ?string $severity = null
    ) {
        $this->minProdQty    = $minProdQty    ?? $this->loadFloatSetting('ai.rule.R2.min_production_qty', 0.0);
        $this->maxEquipHours = $maxEquipHours ?? $this->loadFloatSetting('ai.rule.R2.max_equipment_hours', 0.0);
        $this->severity      = $severity      ?? $this->loadStringSetting('ai.rule.R2.severity', RuleFinding::SEVERITY_HARD);
    }

    public function id(): string { return 'R2'; }
    public function label(): string { return 'Production logged with minimal/no equipment hours'; }

    public function check(JobCardAiFeature $feature): array
    {
        $prodQty = (float) $feature->production_total_qty;
        $equipHours = (float) $feature->equipment_total_hours;

        if ($prodQty <= $this->minProdQty) return [];
        if ($equipHours > $this->maxEquipHours) return [];

        return [
            new RuleFinding(
                code: 'R2_PRODUCTION_NO_EQUIPMENT',
                severity: $this->severity,
                message: sprintf(
                    'Card has %s production qty but only %s equipment hours (threshold: ≤%s).',
                    rtrim(rtrim(number_format($prodQty, 2), '0'), '.'),
                    rtrim(rtrim(number_format($equipHours, 2), '0'), '.'),
                    rtrim(rtrim(number_format($this->maxEquipHours, 2), '0'), '.')
                ),
                ruleId: $this->id(),
                value: $equipHours,
                threshold: $this->maxEquipHours,
                context: [
                    'production_total_qty'   => $prodQty,
                    'equipment_total_hours'  => $equipHours,
                    'min_production_qty'     => $this->minProdQty,
                    'max_equipment_hours'    => $this->maxEquipHours,
                    'configured_severity'    => $this->severity,
                ]
            ),
        ];
    }

    private function loadFloatSetting(string $key, float $default): float
    {
        $val = DB::table('settings')->where('key_name', $key)->value('value');
        return $val !== null ? (float) $val : $default;
    }

    private function loadStringSetting(string $key, string $default): string
    {
        $val = DB::table('settings')->where('key_name', $key)->value('value');
        return $val !== null ? (string) $val : $default;
    }
}
