<?php

namespace App\Services\Ai\Rules;

use App\Models\JobCardAiFeature;
use App\Services\Ai\ValueObjects\RuleFinding;
use Illuminate\Support\Facades\DB;

/**
 * R5: Required production→material pair is missing.
 *
 * Reads from `production_material_pairs` (Sprint 1, S1-09). Each row
 * defines: when production matches `production_pattern`, the listed
 * `expected_material_code` should appear on the same card.
 *
 * Severity:
 *   pair.severity = 'required'    → SEVERITY_HARD (auto-Red)
 *   pair.severity = 'recommended' → SEVERITY_MED (Yellow contributor)
 *
 * is_prefix on pair row:
 *   true   — match if material.description STARTS WITH expected_material_code
 *   false  — match if material.description equals or contains the code
 *
 * UNTIL RON'S CSV IS LOADED: this rule produces no findings (the table
 * is empty). Once the CSV is imported, the same code starts firing.
 *
 * Performance: production_material_pairs is small (~hundreds of rows).
 * Loaded once per rule instance.
 */
class R5PairMismatch implements Rule
{
    /** @var array<int,array> Cached pair rules */
    private array $pairs;

    public function __construct(?array $pairs = null)
    {
        $this->pairs = $pairs ?? $this->loadPairs();
    }

    public function id(): string { return 'R5'; }
    public function label(): string { return 'Required production→material pairing missing'; }

    public function check(JobCardAiFeature $feature): array
    {
        if (empty($this->pairs)) {
            // Table not populated yet (Ron's CSV not loaded). Rule is a no-op.
            return [];
        }

        // Pull production AND material lines for this card.
        $production = DB::table('production')
            ->where('link', $feature->link)
            ->whereNull('deleted_at')
            ->pluck('description');

        if ($production->isEmpty()) {
            return [];
        }

        $materialDescs = DB::table('material')
            ->where('link', $feature->link)
            ->whereNull('deleted_at')
            ->pluck('description')
            ->map(fn ($d) => strtolower(trim($d ?? '')))
            ->filter()
            ->values()
            ->toArray();

        $findings = [];

        foreach ($production as $prodDesc) {
            $prodLower = strtolower(trim($prodDesc ?? ''));
            if ($prodLower === '') continue;

            // Find all pair rules that match this production line
            foreach ($this->pairs as $pair) {
                if (!$this->productionMatches($prodLower, $pair['production_pattern'], $pair['match_mode'])) {
                    continue;
                }

                $expected = strtolower(trim($pair['expected_material_code']));
                $found = $this->materialPresent($materialDescs, $expected, (bool) $pair['is_prefix']);

                if (!$found) {
                    $severity = ($pair['severity'] === 'required')
                        ? RuleFinding::SEVERITY_HARD
                        : RuleFinding::SEVERITY_MED;

                    $code = ($pair['severity'] === 'required')
                        ? 'R5_REQUIRED_PAIR_MISSING'
                        : 'R5_RECOMMENDED_PAIR_MISSING';

                    $findings[] = new RuleFinding(
                        code: $code,
                        severity: $severity,
                        message: sprintf(
                            'Production "%s" usually pairs with material "%s" (%s). Not found on this card.',
                            $prodDesc,
                            $pair['expected_material_code'],
                            $pair['severity']
                        ),
                        ruleId: $this->id(),
                        context: [
                            'production_description' => $prodDesc,
                            'expected_material_code' => $pair['expected_material_code'],
                            'pair_severity'          => $pair['severity'],
                            'match_mode'             => $pair['match_mode'],
                        ]
                    );
                }
            }
        }

        return $findings;
    }

    private function productionMatches(string $prodLower, string $pattern, string $mode): bool
    {
        $pattern = strtolower(trim($pattern));
        return match ($mode) {
            'exact'    => $prodLower === $pattern,
            'contains' => str_contains($prodLower, $pattern),
            'regex'    => @preg_match('/' . $pattern . '/i', $prodLower) === 1,
            default    => false,
        };
    }

    private function materialPresent(array $materialDescsLower, string $expectedLower, bool $isPrefix): bool
    {
        foreach ($materialDescsLower as $matDesc) {
            if ($isPrefix) {
                if (str_starts_with($matDesc, $expectedLower)) {
                    return true;
                }
            } else {
                if ($matDesc === $expectedLower || str_contains($matDesc, $expectedLower)) {
                    return true;
                }
            }
        }
        return false;
    }

    private function loadPairs(): array
    {
        return DB::table('production_material_pairs')
            ->where('active', true)
            ->get([
                'production_pattern',
                'match_mode',
                'expected_material_code',
                'is_prefix',
                'severity',
            ])
            ->map(fn ($r) => (array) $r)
            ->toArray();
    }
}
