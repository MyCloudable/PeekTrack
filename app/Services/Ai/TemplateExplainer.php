<?php

namespace App\Services\Ai;

use App\Services\Ai\ValueObjects\RuleFinding;

/**
 * Layer 4 placeholder: deterministic explanations from rule findings.
 *
 * In Sprint 6 the LLM (Llama 3.1 8B via Ollama) takes over and produces
 * polished prose explanations. Until then, this template-based fallback
 * does the same job using string concatenation.
 *
 * Consumed by:
 *   - jobreviews.decision_reason (one-line summary)
 *   - manager dashboard (full bulleted list)
 *   - email notifications to supers (kicked-back card explanation)
 */
class TemplateExplainer
{
    /**
     * One-line summary suitable for jobreviews.decision_reason.
     *
     * @param RuleFinding[] $findings
     */
    public function summarize(array $findings): string
    {
        if (empty($findings)) {
            return 'No issues detected.';
        }

        $hard = array_filter($findings, fn ($f) => $f->isHard());
        if (!empty($hard)) {
            $first = array_values($hard)[0];
            $extra = count($hard) - 1;
            $base = $first->message;
            return $extra > 0
                ? "{$base} (+{$extra} more issue" . ($extra === 1 ? '' : 's') . ')'
                : $base;
        }

        $count = count($findings);
        return $count === 1
            ? $findings[0]->message
            : "{$count} concerns flagged. " . $findings[0]->message;
    }

    /**
     * Full multi-line explanation for dashboards / emails.
     *
     * @param RuleFinding[] $findings
     */
    public function explain(array $findings): string
    {
        if (empty($findings)) {
            return 'AI review found no issues with this card.';
        }

        $lines = [];
        foreach ($findings as $f) {
            $bullet = match ($f->severity) {
                RuleFinding::SEVERITY_HARD => '✗',
                RuleFinding::SEVERITY_HIGH => '!',
                RuleFinding::SEVERITY_MED  => '·',
                default                    => '·',
            };
            $lines[] = "{$bullet} [{$f->ruleId}] {$f->message}";
        }
        return implode("\n", $lines);
    }
}
