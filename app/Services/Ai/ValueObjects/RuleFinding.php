<?php

namespace App\Services\Ai\ValueObjects;

/**
 * Immutable representation of a single rule violation.
 *
 * Produced by:
 *   RulesEngine (Layer 1) — hard rule findings
 *   ScoringEngine (Layer 2) — soft scoring contributors that exceeded threshold
 *
 * Consumed by:
 *   AiDecisionMaker — assembles into final band/score
 *   TemplateExplainer — renders human-readable explanations
 *   ai_scoring_audit logger — serializes to JSON for persistence
 */
class RuleFinding
{
    public const SEVERITY_HARD = 'hard';     // Layer 1 — automatic Red regardless of score
    public const SEVERITY_HIGH = 'high';     // Layer 2 — strongly pushes toward Red
    public const SEVERITY_MED  = 'medium';   // Layer 2 — Yellow contributor
    public const SEVERITY_LOW  = 'low';      // Informational — minor signal

    public string $code;
    public string $severity;
    public string $message;
    public string $ruleId;
    public ?float $value;
    public ?float $threshold;
    public array $context;

    public function __construct(
        string $code,
        string $severity,
        string $message,
        string $ruleId,
        ?float $value = null,
        ?float $threshold = null,
        array $context = []
    ) {
        $this->code = $code;
        $this->severity = $severity;
        $this->message = $message;
        $this->ruleId = $ruleId;
        $this->value = $value;
        $this->threshold = $threshold;
        $this->context = $context;
    }

    public function isHard(): bool
    {
        return $this->severity === self::SEVERITY_HARD;
    }

    public function toArray(): array
    {
        return [
            'code'      => $this->code,
            'severity'  => $this->severity,
            'message'   => $this->message,
            'rule_id'   => $this->ruleId,
            'value'     => $this->value,
            'threshold' => $this->threshold,
            'context'   => $this->context,
        ];
    }
}
