<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds `crew_type_material_qty_limits` with the data Ron delivered
 * on 2026-04-22 in Materials_List.csv.
 *
 * Idempotent — uses updateOrInsert keyed by the unique tuple.
 *
 * Run:  php artisan db:seed --class=CrewTypeMaterialQtyLimitsSeeder
 *
 * IMPORTANT: This seeder references crew_types.name. The existing
 * crew_types table must already have the expected crew names. If a
 * name doesn't resolve, the row is skipped and a warning is logged
 * so ops can fix the mapping.
 *
 * Ron's CSV (for reference):
 *   Longline     / Thermo   / Tons    / goal=10    / soft=12  / hard=15
 *   Handline     / Thermo   / Tons    / goal=1.5   / soft=2   / hard=2.5
 *   RPM          / Markers  / Each    / goal=1500  / soft=2000/ hard=3000
 *   Paint (temp/paver) / Paint / Gallon / goal="Varies" — SKIPPED, use crew_type_ratio_bands
 *   High Build Paint / Paint / Gallon  / goal=500   / soft=700 / hard=800
 *   Tape      / Tape / LF  / ? ? ? — SKIPPED, covered by R7 COMPLEXITY_OVERRIDE
 *   Removal?  / Removal / LF / ? ? ? — SKIPPED, covered by R7 COMPLEXITY_OVERRIDE
 *
 * Ron's note: "These qty's may need to be changed." → we treat these
 * as v1 seed values and expect tuning during pilot (Week 9).
 */
class CrewTypeMaterialQtyLimitsSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'crew_type_name'  => 'Longline',
                'material_type'   => 'Thermo',
                'unit_of_measure' => 'Tons',
                'goal'            => 10,
                'soft_max'        => 12,
                'hard_max'        => 15,
                'notes'           => 'Ron 2026-04-22: "10 is the goal, 15 is very rare"',
            ],
            [
                'crew_type_name'  => 'Handline',
                'material_type'   => 'Thermo',
                'unit_of_measure' => 'Tons',
                'goal'            => 1.5,
                'soft_max'        => 2,
                'hard_max'        => 2.5,
                'notes'           => 'Ron 2026-04-22',
            ],
            [
                'crew_type_name'  => 'RPM',
                'material_type'   => 'Markers',
                'unit_of_measure' => 'Each',
                'goal'            => 1500,
                'soft_max'        => 2000,
                'hard_max'        => 3000,
                'notes'           => 'Ron 2026-04-22',
            ],
            [
                'crew_type_name'  => 'High Build Paint',
                'material_type'   => 'High Build Paint',
                'unit_of_measure' => 'Gallon',
                'goal'            => 500,
                'soft_max'        => 700,
                'hard_max'        => 800,
                'notes'           => 'Ron 2026-04-22: uses HB Paint material',
            ],
        ];

        $now = now();
        $inserted = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            // Resolve crew_type_name to crew_type_id
            $crewType = DB::table('crew_types')
                ->whereRaw('LOWER(name) = ?', [strtolower($row['crew_type_name'])])
                ->first();

            if (!$crewType) {
                \Log::warning(
                    "[CrewTypeMaterialQtyLimitsSeeder] crew_type '{$row['crew_type_name']}' " .
                    "not found in crew_types table. Row skipped. Ops must either add the " .
                    "crew_type or alias this seeder row."
                );
                $this->command->warn("  Skipped: crew_type '{$row['crew_type_name']}' not found");
                $skipped++;
                continue;
            }

            DB::table('crew_type_material_qty_limits')->updateOrInsert(
                [
                    'crew_type_id'    => $crewType->id,
                    'material_type'   => $row['material_type'],
                    'unit_of_measure' => $row['unit_of_measure'],
                    'active'          => true,
                ],
                [
                    'goal'       => $row['goal'],
                    'soft_max'   => $row['soft_max'],
                    'hard_max'   => $row['hard_max'],
                    'source'     => 'client_provided',
                    'notes'      => $row['notes'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
            $inserted++;
        }

        $this->command->info("Seeded {$inserted} crew_type_material_qty_limits rows from Ron's Materials_List.csv.");
        if ($skipped > 0) {
            $this->command->warn("{$skipped} rows skipped — see storage/logs/laravel.log for details.");
        }

        // Reminder for the team about the deliberately-skipped rows.
        $this->command->info('Rows intentionally skipped from Ron\'s CSV (see seeder docblock):');
        $this->command->line('  - Tape (?/?/?)       → covered by R7 COMPLEXITY_OVERRIDE');
        $this->command->line('  - Removal (?/?/?)    → covered by R7 COMPLEXITY_OVERRIDE');
        $this->command->line('  - Paint (temp/paver) → covered by crew_type_ratio_bands (paint-behind-paver pattern)');
    }
}
