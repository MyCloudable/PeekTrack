<?php

namespace Tests\Feature\Ai;

use App\Services\Ai\UnestimatedItemDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class UnestimatedItemDetectorTest extends TestCase
{
    use RefreshDatabase;

    private UnestimatedItemDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new UnestimatedItemDetector();

        DB::table('settings')->insert([
            'key_name'    => 'ai.unestimated_phase_codes',
            'value'       => json_encode(['98-09000', '98-19999']),
            'value_type'  => 'json',
            'description' => 'Unestimated phase codes',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function test_returns_zero_when_no_lines(): void
    {
        $link = (string) Str::uuid();
        $result = $this->detector->detect($link);

        $this->assertFalse($result['has_unestimated']);
        $this->assertEquals(0, $result['line_count']);
    }

    public function test_detects_98_09000_in_production(): void
    {
        $link = (string) Str::uuid();
        $this->insertProduction($link, '98-09000');

        $result = $this->detector->detect($link);

        $this->assertTrue($result['has_unestimated']);
        $this->assertEquals(1, $result['line_count']);
    }

    public function test_detects_98_19999_in_material(): void
    {
        $link = (string) Str::uuid();
        $this->insertMaterial($link, '98-19999');

        $result = $this->detector->detect($link);

        $this->assertTrue($result['has_unestimated']);
        $this->assertEquals(1, $result['line_count']);
    }

    public function test_counts_across_both_tables(): void
    {
        $link = (string) Str::uuid();
        $this->insertProduction($link, '98-09000');
        $this->insertProduction($link, '98-19999');
        $this->insertMaterial($link, '98-09000');

        $result = $this->detector->detect($link);

        $this->assertTrue($result['has_unestimated']);
        $this->assertEquals(3, $result['line_count']);
    }

    public function test_ignores_non_98_phase_codes(): void
    {
        $link = (string) Str::uuid();
        $this->insertProduction($link, '01-12345');
        $this->insertProduction($link, '02-99999');
        $this->insertMaterial($link, '99-09000'); // close but not 98-

        $result = $this->detector->detect($link);

        $this->assertFalse($result['has_unestimated']);
        $this->assertEquals(0, $result['line_count']);
    }

    public function test_respects_soft_delete(): void
    {
        $link = (string) Str::uuid();
        $this->insertProduction($link, '98-09000', deleted: true);

        $result = $this->detector->detect($link);

        $this->assertFalse($result['has_unestimated']);
        $this->assertEquals(0, $result['line_count']);
    }

    public function test_falls_back_to_default_codes_if_setting_missing(): void
    {
        DB::table('settings')->where('key_name', 'ai.unestimated_phase_codes')->delete();

        $link = (string) Str::uuid();
        $this->insertProduction($link, '98-09000');

        $result = $this->detector->detect($link);

        $this->assertTrue($result['has_unestimated'], 'Should fall back to default codes');
    }

    public function test_falls_back_to_defaults_if_setting_malformed(): void
    {
        DB::table('settings')->where('key_name', 'ai.unestimated_phase_codes')
            ->update(['value' => 'not valid json']);

        $link = (string) Str::uuid();
        $this->insertProduction($link, '98-09000');

        $result = $this->detector->detect($link);

        $this->assertTrue($result['has_unestimated']);
    }

    public function test_uses_custom_phase_codes_from_settings(): void
    {
        DB::table('settings')->where('key_name', 'ai.unestimated_phase_codes')
            ->update(['value' => json_encode(['77-99999'])]);

        $link = (string) Str::uuid();
        $this->insertProduction($link, '98-09000'); // legacy code, not in custom list
        $this->insertProduction($link, '77-99999'); // matches custom list

        $result = $this->detector->detect($link);

        $this->assertTrue($result['has_unestimated']);
        $this->assertEquals(1, $result['line_count']);
    }

    private function insertProduction(string $link, string $phase, bool $deleted = false): void
    {
        DB::table('production')->insert([
            'link' => $link,
            'qty' => 1,
            'phase' => $phase,
            'description' => 'test',
            'deleted_at' => $deleted ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertMaterial(string $link, string $phase, bool $deleted = false): void
    {
        DB::table('material')->insert([
            'link' => $link,
            'qty' => 1,
            'phase' => $phase,
            'description' => 'test',
            'deleted_at' => $deleted ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
