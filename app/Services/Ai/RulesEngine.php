<?php

namespace App\Services\Ai;

use App\Models\JobCardAiFeature;
use App\Services\Ai\Rules\Rule;
use App\Services\Ai\Rules\R1MaterialWithoutProduction;
use App\Services\Ai\Rules\R2ProductionWithoutEquipment;
use App\Services\Ai\Rules\R3EmptyCard;
use App\Services\Ai\Rules\R4QuantityExceedsCeiling;
use App\Services\Ai\Rules\R5PairMismatch;
use App\Services\Ai\Rules\R7RatioBandViolation;
use App\Services\Ai\Rules\R8UnestimatedItems;
use App\Services\Ai\ValueObjects\RuleFinding;

/**
 * Layer 1: runs all hard rules and returns aggregated findings.
 *
 * Rules are loaded once (with their lookup-table data) and reused
 * across many cards in a batch. Don't construct a new RulesEngine per
 * card if you're scoring more than one — let it cache.
 *
 * Adding a new rule:
 *   1. Implement \App\Services\Ai\Rules\Rule
 *   2. Add to defaultRules() below
 *   3. Add a test class
 */
class RulesEngine
{
    /** @var Rule[] */
    private array $rules;

    public function __construct(?array $rules = null)
    {
        $this->rules = $rules ?? $this->defaultRules();
    }

    /**
     * Run every rule against the feature row.
     * Returns flat array of findings across all rules.
     *
     * @param JobCardAiFeature $feature
     * @return RuleFinding[]
     */
    public function evaluate(JobCardAiFeature $feature): array
    {
        $findings = [];
        foreach ($this->rules as $rule) {
            $ruleFindings = $rule->check($feature);
            if (!empty($ruleFindings)) {
                $findings = array_merge($findings, $ruleFindings);
            }
        }
        return $findings;
    }

    /**
     * Are any of the findings hard-severity? (Layer 1 trigger)
     *
     * @param RuleFinding[] $findings
     */
    public function hasHardTrigger(array $findings): bool
    {
        foreach ($findings as $f) {
            if ($f->isHard()) return true;
        }
        return false;
    }

    /**
     * Default rule set. R6 omitted (forbidden pairs not yet specified).
     */
    private function defaultRules(): array
    {
        return [
            new R1MaterialWithoutProduction(),
            new R2ProductionWithoutEquipment(),
            new R3EmptyCard(),
            new R4QuantityExceedsCeiling(),
            new R5PairMismatch(),
            new R7RatioBandViolation(),
            new R8UnestimatedItems(),
        ];
    }
}
