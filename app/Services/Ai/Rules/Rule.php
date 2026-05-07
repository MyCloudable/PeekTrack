<?php

namespace App\Services\Ai\Rules;

use App\Models\JobCardAiFeature;

/**
 * Contract for hard rules (Layer 1) and ratio rules (Layer 2 with hard
 * triggers). Each rule:
 *   - Receives a feature row
 *   - Returns 0+ RuleFinding objects (empty = rule passed)
 *   - Is stateless and side-effect free (no DB writes)
 *
 * Rules requiring lookup table data (R5/R6/R7) inject the data in their
 * constructor, NOT inside check() — keeps DB queries out of the hot path.
 */
interface Rule
{
    /**
     * Stable identifier, e.g. "R1", "R5". Used in audit logs.
     */
    public function id(): string;

    /**
     * Human-readable label for debugging / dashboards.
     */
    public function label(): string;

    /**
     * Run the rule. Return empty array if no violation.
     *
     * @param  JobCardAiFeature $feature
     * @return \App\Services\Ai\ValueObjects\RuleFinding[]
     */
    public function check(JobCardAiFeature $feature): array;
}
