<?php

namespace Tests\Unit\Ai;

use App\Services\Ai\BandClassifier;
use App\Services\Ai\ValueObjects\RuleFinding;
use PHPUnit\Framework\TestCase;

class BandClassifierTest extends TestCase
{
    public function test_low_score_no_findings_is_green(): void
    {
        $c = new BandClassifier(red: 60, yellow: 30);
        $this->assertEquals('green', $c->classify(0.0, []));
        $this->assertEquals('green', $c->classify(29.9, []));
    }

    public function test_yellow_score_is_yellow(): void
    {
        $c = new BandClassifier(red: 60, yellow: 30);
        $this->assertEquals('yellow', $c->classify(30.0, []));
        $this->assertEquals('yellow', $c->classify(45.0, []));
        $this->assertEquals('yellow', $c->classify(59.9, []));
    }

    public function test_red_score_is_red(): void
    {
        $c = new BandClassifier(red: 60, yellow: 30);
        $this->assertEquals('red', $c->classify(60.0, []));
        $this->assertEquals('red', $c->classify(95.0, []));
    }

    public function test_hard_finding_forces_red_regardless_of_score(): void
    {
        $c = new BandClassifier(red: 60, yellow: 30);
        $hard = new RuleFinding(
            'R1_MATERIAL_NO_PRODUCTION',
            RuleFinding::SEVERITY_HARD,
            'msg', 'R1'
        );

        // Score is 0 — would be green without the hard finding
        $this->assertEquals('red', $c->classify(0.0, [$hard]));
    }

    public function test_high_severity_finding_alone_does_not_force_red(): void
    {
        $c = new BandClassifier(red: 60, yellow: 30);
        $high = new RuleFinding('CODE', RuleFinding::SEVERITY_HIGH, 'msg', 'Rx');

        // High alone doesn't trigger Layer 1 — band depends on score
        $this->assertEquals('green', $c->classify(0.0, [$high]));
        $this->assertEquals('yellow', $c->classify(35.0, [$high]));
    }
}
