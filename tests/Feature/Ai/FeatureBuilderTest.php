<?php

namespace Tests\Feature\Ai;

use App\Models\JobCardAiFeature;
use App\Services\Ai\FeatureBuilder;
use App\Services\Ai\UnestimatedItemDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Integration test for FeatureBuilder. Uses a real DB (SQLite in-memory
 * for speed) with the actual Sprint 1 schema applied via migrations.
 *
 * If your test setup uses MySQL/MariaDB instead of SQLite, the SQL is
 * compatible — the only thing to verify is that REGEXP_REPLACE isn't
 * called from PHP code paths (it isn't — only in build_feature_row.sql).
 */
class FeatureBuilderTest extends TestCase
{
    use RefreshDatabase;

    private FeatureBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        // Manually inject the detector — keeps the test independent of
        // service container wiring. Real service is constructor-injected.
        $this->builder = new FeatureBuilder(new UnestimatedItemDetector());

        // Seed minimum settings the detector needs
        DB::table('settings')->insert([
            'key_name' => 'ai.unestimated_phase_codes',
            'value'    => json_encode(['98-09000', '98-19999']),
            'value_type' => 'json',
            'description' => 'Unestimated phase codes',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_builds_basic_feature_row_for_normal_card(): void
    {
        $link = $this->createCard([
            'job_number' => 'J24-0001',
            'workdate'   => '2026-04-01',
            'user_id'    => 1,
        ]);

        $this->addProductionLine($link, ['qty' => 100, 'phase' => '01-12345', 'description' => '4 yel sol fdp']);
        $this->addMaterialLine($link, ['qty' => 50,  'phase' => '02-00001', 'description' => 'SC YEL PAINT', 'supplier' => 'AcmeCo', 'batch' => 'L1234']);
        $this->addEquipmentLine($link, ['hours' => 8, 'truck' => 'T01']);

        $feature = $this->builder->buildOrRefresh($link);

        $this->assertEquals(1, $feature->production_line_count);
        $this->assertEquals(100.0, $feature->production_total_qty);
        $this->assertEquals(50.0, $feature->material_total_qty);
        $this->assertEquals(8.0, $feature->equipment_total_hours);

        // Ratios computed correctly
        $this->assertEquals(0.5, $feature->material_per_production);
        $this->assertEquals(0.08, $feature->equipment_hours_per_production);

        // No hard rule violations
        $this->assertFalse((bool)$feature->has_hard_rule_violation);
        $this->assertFalse((bool)$feature->has_unestimated_items);
    }

    public function test_detects_r1_violation_material_without_production(): void
    {
        $link = $this->createCard();
        $this->addMaterialLine($link, ['qty' => 50, 'description' => 'SC WHT PAINT']);
        // No production, no equipment

        $feature = $this->builder->buildOrRefresh($link);

        $this->assertTrue((bool)$feature->has_hard_rule_violation);
        $this->assertEquals(0, $feature->production_total_qty);
        $this->assertNull($feature->material_per_production); // div-by-zero → NULL
    }

    public function test_detects_r2_violation_production_without_equipment(): void
    {
        $link = $this->createCard();
        $this->addProductionLine($link, ['qty' => 100]);
        $this->addMaterialLine($link, ['qty' => 50]);
        // No equipment

        $feature = $this->builder->buildOrRefresh($link);

        $this->assertTrue((bool)$feature->has_hard_rule_violation);
        $this->assertEquals(0, $feature->equipment_total_hours);
    }

    public function test_detects_r3_violation_empty_card(): void
    {
        $link = $this->createCard();
        // No production, no material, no equipment

        $feature = $this->builder->buildOrRefresh($link);

        $this->assertTrue((bool)$feature->has_hard_rule_violation);
    }

    public function test_detects_unestimated_items_via_phase_code_in_production(): void
    {
        $link = $this->createCard();
        $this->addProductionLine($link, ['qty' => 5, 'phase' => '98-09000', 'description' => 'Unestimated Production']);
        $this->addProductionLine($link, ['qty' => 100, 'phase' => '01-12345', 'description' => 'normal item']);
        $this->addEquipmentLine($link, ['hours' => 8]);

        $feature = $this->builder->buildOrRefresh($link);

        $this->assertTrue((bool)$feature->has_unestimated_items);
        $this->assertEquals(1, $feature->unestimated_line_count);
    }

    public function test_detects_unestimated_items_via_phase_code_in_material(): void
    {
        $link = $this->createCard();
        $this->addProductionLine($link, ['qty' => 100]);
        $this->addMaterialLine($link, ['qty' => 5, 'phase' => '98-19999', 'description' => 'Unestimated Materials']);
        $this->addEquipmentLine($link, ['hours' => 8]);

        $feature = $this->builder->buildOrRefresh($link);

        $this->assertTrue((bool)$feature->has_unestimated_items);
        $this->assertEquals(1, $feature->unestimated_line_count);
    }

    public function test_counts_missing_supplier_and_batch(): void
    {
        $link = $this->createCard();
        $this->addProductionLine($link, ['qty' => 100]);
        $this->addEquipmentLine($link, ['hours' => 8]);
        $this->addMaterialLine($link, ['supplier' => 'AcmeCo', 'batch' => 'L1']);
        $this->addMaterialLine($link, ['supplier' => '',       'batch' => 'L2']); // missing supplier
        $this->addMaterialLine($link, ['supplier' => 'AcmeCo', 'batch' => null]); // missing batch
        $this->addMaterialLine($link, ['supplier' => null,     'batch' => '']);   // missing both

        $feature = $this->builder->buildOrRefresh($link);

        $this->assertEquals(4, $feature->material_line_count);
        $this->assertEquals(2, $feature->material_missing_supplier_cnt);
        $this->assertEquals(2, $feature->material_missing_batch_cnt);
    }

    public function test_excludes_soft_deleted_lines(): void
    {
        $link = $this->createCard();
        $this->addProductionLine($link, ['qty' => 100]);
        $this->addProductionLine($link, ['qty' => 9999, 'deleted_at' => now()]); // should be ignored
        $this->addEquipmentLine($link, ['hours' => 8]);

        $feature = $this->builder->buildOrRefresh($link);

        $this->assertEquals(1, $feature->production_line_count);
        $this->assertEquals(100.0, $feature->production_total_qty);
    }

    public function test_idempotent_on_repeated_invocation(): void
    {
        $link = $this->createCard();
        $this->addProductionLine($link, ['qty' => 100]);
        $this->addEquipmentLine($link, ['hours' => 8]);

        $first  = $this->builder->buildOrRefresh($link);
        $second = $this->builder->buildOrRefresh($link);

        $this->assertEquals($first->id, $second->id); // same row, updated in place
        $this->assertEquals(1, JobCardAiFeature::where('link', $link)->count());
    }

    public function test_throws_on_missing_card(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->builder->buildOrRefresh('00000000-0000-0000-0000-000000000000');
    }

    public function test_notes_length_captured(): void
    {
        $link = $this->createCard(['notes' => 'Crew finished early due to rain']);
        $this->addProductionLine($link, ['qty' => 100]);
        $this->addEquipmentLine($link, ['hours' => 8]);

        $feature = $this->builder->buildOrRefresh($link);

        $this->assertEquals(31, $feature->notes_length);
    }

    public function test_equipment_only_reason_persisted(): void
    {
        $link = $this->createCard([
            'equipment_only_reason'      => 'rain',
            'equipment_only_reason_text' => 'Heavy rain stopped work at 11am',
        ]);
        $this->addEquipmentLine($link, ['hours' => 4]);

        $feature = $this->builder->buildOrRefresh($link);

        $this->assertEquals('rain', $feature->equipment_only_reason);
        $this->assertStringContainsString('Heavy rain', $feature->equipment_only_reason_text);
    }

    // ── Test helpers ──

    private function createCard(array $overrides = []): string
    {
        $link = (string) Str::uuid();
        DB::table('jobentries')->insert(array_merge([
            'link'         => $link,
            'job_number'   => 'J-TEST',
            'workdate'     => now()->toDateString(),
            'submitted'    => 1,
            'approved'     => null,
            'review_state' => 'pending_ai',
            'user_id'      => 1,
            'crew_type_id' => 1,
            'created_at'   => now(),
            'updated_at'   => now(),
        ], $overrides));
        return $link;
    }

    private function addProductionLine(string $link, array $overrides = []): void
    {
        DB::table('production')->insert(array_merge([
            'link'        => $link,
            'qty'         => 0,
            'phase'       => '01-00001',
            'description' => 'test production',
            'created_at'  => now(),
            'updated_at'  => now(),
        ], $overrides));
    }

    private function addMaterialLine(string $link, array $overrides = []): void
    {
        DB::table('material')->insert(array_merge([
            'link'        => $link,
            'qty'         => 0,
            'phase'       => '02-00001',
            'description' => 'test material',
            'supplier'    => 'TestVendor',
            'batch'       => 'TestBatch',
            'created_at'  => now(),
            'updated_at'  => now(),
        ], $overrides));
    }

    private function addEquipmentLine(string $link, array $overrides = []): void
    {
        DB::table('equipment')->insert(array_merge([
            'link'       => $link,
            'hours'      => 0,
            'truck'      => 'T-TEST',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }
}
