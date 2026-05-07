<?php

namespace App\Services\Ai;

use App\Services\Ai\ValueObjects\RuleFinding;
use Illuminate\Support\Facades\DB;

/**
 * Maps (score, findings) → band.
 *
 * Rules:
 *   - ANY hard finding (Layer 1) → red, regardless of score
 *   - score ≥ red_threshold      → red
 *   - score ≥ yellow_threshold   → yellow
 *   - else                       → green
 *
 * Thresholds are configurable via settings:
 *   ai.band_threshold.red    (default 60)
 *   ai.band_threshold.yellow (default 30)
 */
class BandClassifier
{
    public const BAND_GREEN  = 'green';
    public const BAND_YELLOW = 'yellow';
    public const BAND_RED    = 'red';

    private float $redThreshold;
    private float $yellowThreshold;

    public function __construct(?float $red = null, ?float $yellow = null)
    {
        $this->redThreshold    = $red    ?? $this->loadThreshold('red',    60.0);
        $this->yellowThreshold = $yellow ?? $this->loadThreshold('yellow', 30.0);
    }

    /**
     * @param float $score
     * @param RuleFinding[] $findings
     */
    public function classify(float $score, array $findings): string
    {
        // Layer 1 trigger always wins
        foreach ($findings as $f) {
            if ($f->isHard()) {
                return self::BAND_RED;
            }
        }

        if ($score >= $this->redThreshold)    return self::BAND_RED;
        if ($score >= $this->yellowThreshold) return self::BAND_YELLOW;
        return self::BAND_GREEN;
    }

    private function loadThreshold(string $name, float $default): float
    {
        $val = DB::table('settings')
            ->where('key_name', "ai.band_threshold.{$name}")
            ->value('value');
        return $val !== null ? (float) $val : $default;
    }
}
