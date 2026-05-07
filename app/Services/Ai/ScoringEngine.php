<?php

namespace App\Services\Ai;

use App\Services\Ai\ValueObjects\RuleFinding;
use Illuminate\Support\Facades\DB;

/**
 * Layer 2: converts an array of rule findings into a 0-100 risk score
 * plus a structured breakdown for the audit log.
 *
 * Scoring philosophy:
 *   - Hard rules (Layer 1) → score is irrelevant, card is auto-Red.
 *     But we still compute and persist the score for ML training.
 *   - Each finding contributes a weighted amount to the score.
 *   - Weights live in `settings` so ops can tune without a deploy.
 *   - Score caps at 100. Going negative is impossible.
 *
 * Weight defaults (from spec §6, persisted by AiSettingsSeeder):
 *   ai.score_weight.R4_HARD_CEILING_EXCEEDED        = 60
 *   ai.score_weight.R4_SOFT_CEILING_EXCEEDED        = 25
 *   ai.score_weight.R5_REQUIRED_PAIR_MISSING        = 50
 *   ai.score_weight.R5_RECOMMENDED_PAIR_MISSING     = 15
 *   ai.score_weight.R7_MATERIAL_RATIO_OUT_OF_BAND   = 30
 *   ai.score_weight.R7_EQUIPMENT_RATIO_OUT_OF_BAND  = 30
 *   ai.score_weight.R8_UNESTIMATED_ITEMS            = 20
 *
 * Hard rules don't have weights — they bypass scoring and produce auto-Red
 * via BandClassifier. But we record their codes in the breakdown for
 * forensic clarity.
 */
class ScoringEngine
{
    private array $weights;

    public function __construct(?array $weights = null)
    {
        $this->weights = $weights ?? $this->loadWeights();
    }

    /**
     * @param RuleFinding[] $findings
     * @return array{score: float, breakdown: array}
     */
    public function score(array $findings): array
    {
        $score = 0.0;
        $breakdown = [];

        foreach ($findings as $f) {
            $weight = $this->weights[$f->code] ?? $this->defaultWeightFor($f->severity);
            $contribution = $f->isHard() ? 0 : $weight;

            $score += $contribution;
            $breakdown[] = [
                'code'         => $f->code,
                'severity'     => $f->severity,
                'weight'       => $weight,
                'contribution' => $contribution,
                'rule_id'      => $f->ruleId,
            ];
        }

        $score = min(100.0, max(0.0, $score));

        return [
            'score'     => round($score, 2),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Fallback weights when a finding code isn't explicitly configured.
     * Keeps the engine working even if settings aren't fully seeded.
     */
    private function defaultWeightFor(string $severity): float
    {
        return match ($severity) {
            RuleFinding::SEVERITY_HARD => 0,    // hard bypasses scoring
            RuleFinding::SEVERITY_HIGH => 30,
            RuleFinding::SEVERITY_MED  => 15,
            RuleFinding::SEVERITY_LOW  => 5,
            default                    => 0,
        };
    }

    private function loadWeights(): array
    {
        return DB::table('settings')
            ->where('key_name', 'LIKE', 'ai.score_weight.%')
            ->get(['key_name', 'value'])
            ->mapWithKeys(function ($r) {
                $code = substr($r->key_name, strlen('ai.score_weight.'));
                return [$code => (float) $r->value];
            })
            ->toArray();
    }
}
