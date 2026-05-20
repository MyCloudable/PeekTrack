<?php

namespace App\Services\Ai\Rules;

use App\Models\JobCardAiFeature;
use App\Services\Ai\ValueObjects\RuleFinding;
use Illuminate\Support\Facades\DB;

/**
 * R1: Material was used but no production was logged.
 *
 * v1 (Sprint 3): hard rule, any material qty > 0 with zero production = Red.
 * v2 (Sprint 4.5): configurable threshold + severity.
 *
 * Calibration showed v1 was overcalling — many cards with small material qty
 * and no production are legitimate (cleanup, prep, leftover). Configurable
 * threshold lets ops set "fire only if material qty exceeds X" without a
 * deploy.
 *
 * SETTINGS
 *   ai.rule.R1.min_material_qty    (default 0)
 *     Only fire if material_total_qty > this value
 *
 *   ai.rule.R1.severity            (default 'hard')
 *     One of: 'hard' | 'high' | 'medium' | 'low'
 *     'hard' → forces Red (Layer 1)
 *     'high'/'medium'/'low' → soft contributor to Layer 2 score
 *
 * BEHAVIOR
 *   If material_total_qty <= min_material_qty: rule passes (no finding)
 *   Else: produces a finding at the configured severity level
 */
class R1MaterialWithoutProduction implements Rule
{
    private float $minQty;
    private string $severity;

    public function __construct(?float $minQty = null, ?string $severity = null)
    {
        $this->minQty   = $minQty   ?? $this->loadFloatSetting('ai.rule.R1.min_material_qty', 0.0);
        $this->severity = $severity ?? $this->loadStringSetting('ai.rule.R1.severity', RuleFinding::SEVERITY_HARD);
    }

    public function id(): string { return 'R1'; }
    public function label(): string { return 'Material logged without production'; }

    public function check(JobCardAiFeature $feature): array
    {
        $matQty = (float) $feature->material_total_qty;
        $prodQty = (float) $feature->production_total_qty;

        if ($matQty <= $this->minQty) return [];
        if ($prodQty > 0) return [];

        return [
            new RuleFinding(
                code: 'R1_MATERIAL_NO_PRODUCTION',
                severity: $this->severity,
                message: sprintf(
                    'Card has %s material qty but 0 production. Add production lines or mark as equipment-only.',
                    rtrim(rtrim(number_format($matQty, 2), '0'), '.')
                ),
                ruleId: $this->id(),
                value: $matQty,
                threshold: $this->minQty,
                context: [
                    'material_total_qty'   => $matQty,
                    'production_total_qty' => $prodQty,
                    'configured_min_qty'   => $this->minQty,
                    'configured_severity'  => $this->severity,
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
