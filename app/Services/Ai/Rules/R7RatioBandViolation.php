<?php

namespace App\Services\Ai\Rules;

use App\Models\JobCardAiFeature;
use App\Services\Ai\ValueObjects\RuleFinding;
use Illuminate\Support\Facades\DB;

/**
 * R7: Material-per-production or equipment-hours-per-production ratio
 *      is outside the configured band for this crew type.
 *
 * Reads from `crew_type_ratio_bands` (Sprint 1, S1-11). Each row defines
 * (crew_type_id, metric, lower_bound, upper_bound).
 *
 * If no row exists for the card's (crew_type, metric), this rule passes
 * silently — global fallback is to be added in Sprint 5 when ML is wired in.
 *
 * Severity:
 *   - Outside the band → SEVERITY_HIGH (Layer 2 contributor)
 *   - We DON'T raise this to HARD because ratio anomalies are
 *     legitimately common (paint-behind-paver, partial-day work, etc.)
 *     and humans should review rather than auto-reject.
 */
class R7RatioBandViolation implements Rule
{
    /**
     * Bands keyed by [crew_type_id][metric] = ['lower' => x, 'upper' => y].
     * @var array<int, array<string, array{lower:?float, upper:?float}>>
     */
    private array $bands;

    public function __construct(?array $bands = null)
    {
        $this->bands = $bands ?? $this->loadBands();
    }

    public function id(): string { return 'R7'; }
    public function label(): string { return 'Material/equipment-to-production ratio outside crew band'; }

    public function check(JobCardAiFeature $feature): array
    {
        if (empty($this->bands)) return [];
        if (!$feature->crew_type_id) return [];

        $crewBands = $this->bands[$feature->crew_type_id] ?? null;
        if (!$crewBands) return [];

        $findings = [];

        // Material per production
        if (!is_null($feature->material_per_production)
            && isset($crewBands['material_per_production'])) {
            $f = $this->checkBand(
                $feature->material_per_production,
                $crewBands['material_per_production'],
                'material_per_production',
                'R7_MATERIAL_RATIO_OUT_OF_BAND',
                'Material per production'
            );
            if ($f) $findings[] = $f;
        }

        // Equipment hours per production
        if (!is_null($feature->equipment_hours_per_production)
            && isset($crewBands['equipment_hours_per_production'])) {
            $f = $this->checkBand(
                $feature->equipment_hours_per_production,
                $crewBands['equipment_hours_per_production'],
                'equipment_hours_per_production',
                'R7_EQUIPMENT_RATIO_OUT_OF_BAND',
                'Equipment hours per production'
            );
            if ($f) $findings[] = $f;
        }

        return $findings;
    }

    private function checkBand(float $value, array $band, string $metric, string $code, string $label): ?RuleFinding
    {
        $lower = $band['lower'];
        $upper = $band['upper'];

        $belowLower = !is_null($lower) && $value < $lower;
        $aboveUpper = !is_null($upper) && $value > $upper;

        if (!$belowLower && !$aboveUpper) return null;

        $direction = $belowLower ? 'below' : 'above';
        $threshold = $belowLower ? $lower : $upper;

        return new RuleFinding(
            code: $code,
            severity: RuleFinding::SEVERITY_HIGH,
            message: sprintf(
                '%s ratio %s is %s the expected band for this crew type (%s %s).',
                $label,
                number_format($value, 3),
                $direction,
                $direction === 'below' ? 'min' : 'max',
                number_format($threshold, 3)
            ),
            ruleId: $this->id(),
            value: $value,
            threshold: $threshold,
            context: [
                'metric'    => $metric,
                'direction' => $direction,
                'lower'     => $lower,
                'upper'     => $upper,
            ]
        );
    }

    private function loadBands(): array
    {
        $bands = [];
        $rows = DB::table('crew_type_ratio_bands')
            ->where('active', true)
            ->get(['crew_type_id', 'metric', 'lower_bound', 'upper_bound']);

        foreach ($rows as $r) {
            $bands[$r->crew_type_id][$r->metric] = [
                'lower' => is_null($r->lower_bound) ? null : (float) $r->lower_bound,
                'upper' => is_null($r->upper_bound) ? null : (float) $r->upper_bound,
            ];
        }

        return $bands;
    }
}
