<?php

namespace App\Services\Ai\Rules;

use App\Models\JobCardAiFeature;
use App\Services\Ai\ValueObjects\RuleFinding;
use Illuminate\Support\Facades\DB;

/**
 * R4: Production quantity exceeds the configured ceiling.
 *
 * Reads from `production_qty_limits` (Sprint 1, S1-10). Each row defines
 * a (production_pattern, unit_of_measure) → soft_max / hard_max / daily_max.
 *
 * Pattern matching:
 *   exact     — production.description == pattern (case-insensitive)
 *   contains  — production.description LIKE %pattern%
 *   regex     — production.description matches pattern as regex
 *
 * Behavior:
 *   - Card production lines are pulled and checked against patterns.
 *   - hard_max exceeded → SEVERITY_HARD (auto-Red regardless of score)
 *   - soft_max exceeded → SEVERITY_HIGH (heavy Layer 2 contributor)
 *   - daily_max — not a per-line cap, it's the per-card-per-day max.
 *     Compared against feature->production_total_qty for the card.
 *
 * Performance: ceilings table is small (~hundreds of rows max). Loaded
 * once per rule instance and cached on the object. RulesEngine should
 * hold one instance across the batch.
 */
class R4QuantityExceedsCeiling implements Rule
{
    /** @var array<int,array> Cached ceiling rows from production_qty_limits */
    private array $ceilings;

    public function __construct(?array $ceilings = null)
    {
        $this->ceilings = $ceilings ?? $this->loadCeilings();
    }

    public function id(): string { return 'R4'; }
    public function label(): string { return 'Production quantity exceeds configured ceiling'; }

    public function check(JobCardAiFeature $feature): array
    {
        if (empty($this->ceilings)) {
            return [];
        }

        // Pull production lines for this card.
        $lines = DB::table('production')
            ->where('link', $feature->link)
            ->whereNull('deleted_at')
            ->select(['description', 'qty', 'unit_of_measure'])
            ->get();

        if ($lines->isEmpty()) {
            return [];
        }

        $findings = [];

        foreach ($lines as $line) {
            $desc = strtolower(trim($line->description ?? ''));
            $unit = strtolower(trim($line->unit_of_measure ?? ''));
            $qty  = (float) ($line->qty ?? 0);

            foreach ($this->ceilings as $c) {
                if (strtolower($c['unit_of_measure']) !== $unit) {
                    continue;
                }
                if (!$this->matches($desc, $c['production_pattern'], $c['match_mode'])) {
                    continue;
                }

                // Hard cap?
                if (!is_null($c['hard_max']) && $qty > $c['hard_max']) {
                    $findings[] = new RuleFinding(
                        code: 'R4_HARD_CEILING_EXCEEDED',
                        severity: RuleFinding::SEVERITY_HARD,
                        message: sprintf(
                            '%s qty %s %s exceeds hard ceiling of %s.',
                            $line->description,
                            number_format($qty, 2),
                            $line->unit_of_measure,
                            number_format($c['hard_max'], 2)
                        ),
                        ruleId: $this->id(),
                        value: $qty,
                        threshold: (float) $c['hard_max'],
                        context: [
                            'pattern' => $c['production_pattern'],
                            'unit'    => $c['unit_of_measure'],
                            'cap_kind' => 'hard_max',
                        ]
                    );
                    break;
                }

                // Soft cap?
                if (!is_null($c['soft_max']) && $qty > $c['soft_max']) {
                    $findings[] = new RuleFinding(
                        code: 'R4_SOFT_CEILING_EXCEEDED',
                        severity: RuleFinding::SEVERITY_HIGH,
                        message: sprintf(
                            '%s qty %s %s exceeds soft ceiling of %s.',
                            $line->description,
                            number_format($qty, 2),
                            $line->unit_of_measure,
                            number_format($c['soft_max'], 2)
                        ),
                        ruleId: $this->id(),
                        value: $qty,
                        threshold: (float) $c['soft_max'],
                        context: [
                            'pattern' => $c['production_pattern'],
                            'unit'    => $c['unit_of_measure'],
                            'cap_kind' => 'soft_max',
                        ]
                    );
                    break;
                }
            }
        }

        return $findings;
    }

    private function matches(string $description, string $pattern, string $mode): bool
    {
        $pattern = strtolower(trim($pattern));
        return match ($mode) {
            'exact'    => $description === $pattern,
            'contains' => str_contains($description, $pattern),
            'regex'    => @preg_match('/' . $pattern . '/i', $description) === 1,
            default    => false,
        };
    }

    private function loadCeilings(): array
    {
        return DB::table('production_qty_limits')
            ->where('active', true)
            ->get([
                'production_pattern',
                'match_mode',
                'unit_of_measure',
                'soft_max',
                'hard_max',
                'daily_max',
            ])
            ->map(fn ($r) => (array) $r)
            ->toArray();
    }
}
