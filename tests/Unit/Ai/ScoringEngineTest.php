<?php

namespace Tests\Unit\Ai;

use App\Services\Ai\ScoringEngine;
use App\Services\Ai\ValueObjects\RuleFinding;
use PHPUnit\Framework\TestCase;

class ScoringEngineTest extends TestCase
{
    private function finding(string $code, string $severity): RuleFinding
    {
        return new RuleFinding($code, $severity, "msg for {$code}", 'R-test');
    }

    public function test_empty_findings_score_zero(): void
    {
        $engine = new ScoringEngine([]);
        $result = $engine->score([]);

        $this->assertEquals(0.0, $result['score']);
        $this->assertEmpty($result['breakdown']);
    }

    public function test_hard_findings_do_not_contribute_to_score(): void
    {
        $engine = new ScoringEngine([
            'R1_MATERIAL_NO_PRODUCTION' => 999,  // weight ignored for hard
        ]);

        $result = $engine->score([
            $this->finding('R1_MATERIAL_NO_PRODUCTION', RuleFinding::SEVERITY_HARD),
        ]);

        $this->assertEquals(0.0, $result['score']);
        $this->assertEquals(0, $result['breakdown'][0]['contribution']);
    }

    public function test_uses_explicit_weights_when_configured(): void
    {
        $engine = new ScoringEngine([
            'R5_RECOMMENDED_PAIR_MISSING' => 12.5,
            'R8_UNESTIMATED_ITEMS'        => 22,
        ]);

        $result = $engine->score([
            $this->finding('R5_RECOMMENDED_PAIR_MISSING', RuleFinding::SEVERITY_MED),
            $this->finding('R8_UNESTIMATED_ITEMS',        RuleFinding::SEVERITY_HIGH),
        ]);

        $this->assertEquals(34.5, $result['score']);
    }

    public function test_falls_back_to_severity_default_when_weight_missing(): void
    {
        $engine = new ScoringEngine([]);  // no weights configured

        $result = $engine->score([
            $this->finding('UNKNOWN_CODE_HIGH', RuleFinding::SEVERITY_HIGH),
            $this->finding('UNKNOWN_CODE_MED',  RuleFinding::SEVERITY_MED),
            $this->finding('UNKNOWN_CODE_LOW',  RuleFinding::SEVERITY_LOW),
        ]);

        // Defaults: high=30, med=15, low=5
        $this->assertEquals(50.0, $result['score']);
    }

    public function test_score_caps_at_100(): void
    {
        $engine = new ScoringEngine([
            'A' => 60,
            'B' => 60,
        ]);

        $result = $engine->score([
            $this->finding('A', RuleFinding::SEVERITY_HIGH),
            $this->finding('B', RuleFinding::SEVERITY_HIGH),
        ]);

        $this->assertEquals(100.0, $result['score']);
    }

    public function test_breakdown_includes_one_entry_per_finding(): void
    {
        $engine = new ScoringEngine(['CODE_A' => 10, 'CODE_B' => 20]);
        $result = $engine->score([
            $this->finding('CODE_A', RuleFinding::SEVERITY_MED),
            $this->finding('CODE_B', RuleFinding::SEVERITY_HIGH),
        ]);

        $this->assertCount(2, $result['breakdown']);
        $this->assertEquals('CODE_A', $result['breakdown'][0]['code']);
        $this->assertEquals(10, $result['breakdown'][0]['weight']);
        $this->assertEquals('CODE_B', $result['breakdown'][1]['code']);
        $this->assertEquals(20, $result['breakdown'][1]['weight']);
    }
}
