<?php

namespace Tests\Unit\Ai;

use App\Services\Ai\TemplateExplainer;
use App\Services\Ai\ValueObjects\RuleFinding;
use PHPUnit\Framework\TestCase;

class TemplateExplainerTest extends TestCase
{
    private TemplateExplainer $explainer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->explainer = new TemplateExplainer();
    }

    public function test_no_findings_yields_clean_message(): void
    {
        $this->assertSame('No issues detected.', $this->explainer->summarize([]));
    }

    public function test_single_finding_returns_its_message(): void
    {
        $f = new RuleFinding('X', RuleFinding::SEVERITY_MED, 'qty exceeds soft cap', 'R4');
        $this->assertSame('qty exceeds soft cap', $this->explainer->summarize([$f]));
    }

    public function test_hard_finding_takes_precedence_in_summary(): void
    {
        $hard = new RuleFinding('H', RuleFinding::SEVERITY_HARD, 'card is empty', 'R3');
        $med  = new RuleFinding('M', RuleFinding::SEVERITY_MED,  'minor issue',   'Rx');

        $summary = $this->explainer->summarize([$med, $hard]);
        $this->assertStringContainsString('card is empty', $summary);
    }

    public function test_multiple_hard_findings_show_count(): void
    {
        $h1 = new RuleFinding('H1', RuleFinding::SEVERITY_HARD, 'first issue', 'R1');
        $h2 = new RuleFinding('H2', RuleFinding::SEVERITY_HARD, 'second',      'R2');

        $summary = $this->explainer->summarize([$h1, $h2]);
        $this->assertStringContainsString('first issue', $summary);
        $this->assertStringContainsString('+1 more', $summary);
    }

    public function test_explain_lists_each_finding(): void
    {
        $a = new RuleFinding('A', RuleFinding::SEVERITY_HARD, 'first',  'R1');
        $b = new RuleFinding('B', RuleFinding::SEVERITY_HIGH, 'second', 'R7');
        $c = new RuleFinding('C', RuleFinding::SEVERITY_MED,  'third',  'R5');

        $output = $this->explainer->explain([$a, $b, $c]);
        $lines = explode("\n", $output);

        $this->assertCount(3, $lines);
        $this->assertStringContainsString('R1', $lines[0]);
        $this->assertStringContainsString('R7', $lines[1]);
        $this->assertStringContainsString('R5', $lines[2]);
    }
}
