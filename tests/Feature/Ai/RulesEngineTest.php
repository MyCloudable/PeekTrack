<?php

namespace Tests\Feature\Ai;

use App\Models\JobCardAiFeature;
use App\Services\Ai\Rules\R1MaterialWithoutProduction;
use App\Services\Ai\Rules\R2ProductionWithoutEquipment;
use App\Services\Ai\Rules\R3EmptyCard;
use App\Services\Ai\Rules\R8UnestimatedItems;
use App\Services\Ai\RulesEngine;
use App\Services\Ai\ValueObjects\RuleFinding;
use Tests\TestCase;

class RulesEngineTest extends TestCase
{
    /**
     * Build a feature row by hand (no DB) to keep this test fast and
     * decoupled from the schema.
     */
    private function feature(array $overrides = []): JobCardAiFeature
    {
        $f = new JobCardAiFeature();
        $defaults = [
            'link'                            => 'test-' . uniqid(),
            'job_number'                      => 'J-TEST',
            'workdate'                        => '2026-04-15',
            'production_total_qty'            => 100.0,
            'material_total_qty'              => 25.0,
            'equipment_total_hours'           => 8.0,
            'has_unestimated_items'           => false,
            'unestimated_line_count'          => 0,
            'equipment_only_reason'           => null,
            'crew_type_id'                    => 1,
            'feature_version'                 => 'v1',
        ];
        foreach (array_merge($defaults, $overrides) as $k => $v) {
            $f->setAttribute($k, $v);
        }
        return $f;
    }

    public function test_clean_card_produces_no_findings(): void
    {
        $engine = new RulesEngine([
            new R1MaterialWithoutProduction(),
            new R2ProductionWithoutEquipment(),
            new R3EmptyCard(),
            new R8UnestimatedItems(),
        ]);

        $findings = $engine->evaluate($this->feature());

        $this->assertEmpty($findings);
        $this->assertFalse($engine->hasHardTrigger($findings));
    }

    public function test_r1_fires_on_material_without_production(): void
    {
        $engine = new RulesEngine([
            new R1MaterialWithoutProduction(),
        ]);

        $findings = $engine->evaluate($this->feature([
            'production_total_qty' => 0,
            'material_total_qty'   => 50,
        ]));

        $this->assertCount(1, $findings);
        $this->assertEquals('R1_MATERIAL_NO_PRODUCTION', $findings[0]->code);
        $this->assertTrue($engine->hasHardTrigger($findings));
    }

    public function test_r3_fires_on_truly_empty_card(): void
    {
        $engine = new RulesEngine([new R3EmptyCard()]);
        $findings = $engine->evaluate($this->feature([
            'production_total_qty'  => 0,
            'material_total_qty'    => 0,
            'equipment_total_hours' => 0,
        ]));

        $this->assertCount(1, $findings);
        $this->assertEquals('R3_EMPTY_CARD', $findings[0]->code);
    }

    public function test_r3_skips_empty_card_with_equipment_only_reason(): void
    {
        $engine = new RulesEngine([new R3EmptyCard()]);
        $findings = $engine->evaluate($this->feature([
            'production_total_qty'  => 0,
            'material_total_qty'    => 0,
            'equipment_total_hours' => 0,
            'equipment_only_reason' => 'rain',
        ]));

        $this->assertEmpty($findings);
    }

    public function test_r8_fires_when_unestimated_flag_set(): void
    {
        $engine = new RulesEngine([new R8UnestimatedItems()]);
        $findings = $engine->evaluate($this->feature([
            'has_unestimated_items'  => true,
            'unestimated_line_count' => 3,
        ]));

        $this->assertCount(1, $findings);
        $this->assertEquals('R8_UNESTIMATED_ITEMS', $findings[0]->code);
        $this->assertEquals(RuleFinding::SEVERITY_HIGH, $findings[0]->severity);
        $this->assertFalse($engine->hasHardTrigger($findings));
    }

    public function test_multiple_rules_fire_independently(): void
    {
        $engine = new RulesEngine([
            new R1MaterialWithoutProduction(),
            new R2ProductionWithoutEquipment(),
            new R8UnestimatedItems(),
        ]);

        // Material w/o production AND unestimated
        $findings = $engine->evaluate($this->feature([
            'production_total_qty'   => 0,
            'material_total_qty'     => 25,
            'has_unestimated_items'  => true,
            'unestimated_line_count' => 1,
        ]));

        $this->assertCount(2, $findings);
        $codes = array_map(fn ($f) => $f->code, $findings);
        $this->assertContains('R1_MATERIAL_NO_PRODUCTION', $codes);
        $this->assertContains('R8_UNESTIMATED_ITEMS', $codes);
    }
}
