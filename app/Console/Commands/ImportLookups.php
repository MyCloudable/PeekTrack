<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Generic CSV loader for the AI lookup tables.
 *
 * SUPPORTED LOOKUP TYPES
 *   pairings      → production_material_pairs (S1-13)
 *   ceilings      → production_qty_limits (S1-14)
 *   crew-bands    → crew_type_ratio_bands (S1-15)
 *   crew-mat-qty  → crew_type_material_qty_limits (S1-17, Ron's CSV format)
 *
 * USAGE
 *   php artisan ai:import-lookups pairings ron-confirmed.csv
 *   php artisan ai:import-lookups ceilings ceilings.csv --dry-run
 *   php artisan ai:import-lookups crew-bands bands.csv
 *   php artisan ai:import-lookups crew-mat-qty Materials_List.csv
 *
 * NOTE
 *   Ron's specific pairings format (with primary_materials/borderline_materials
 *   columns and partial values) is handled by ImportPairings.php. This
 *   command's "pairings" mode handles the simpler one-row-per-pair format
 *   used for direct pair specifications (post-import-pairings cleanup).
 */
class ImportLookups extends Command
{
    protected $signature = 'ai:import-lookups
                            {type : pairings | ceilings | crew-bands | crew-mat-qty}
                            {csv : Path to CSV file}
                            {--dry-run : Preview without writing}';

    protected $description = 'Import AI lookup data from CSV (pairings, ceilings, crew bands)';

    private const HANDLERS = [
        'pairings'     => 'importPairings',
        'ceilings'     => 'importCeilings',
        'crew-bands'   => 'importCrewBands',
        'crew-mat-qty' => 'importCrewMatQty',
    ];

    public function handle(): int
    {
        $type = $this->argument('type');
        $path = $this->argument('csv');

        if (!isset(self::HANDLERS[$type])) {
            $this->error("Unknown type: {$type}");
            $this->line("Supported: " . implode(', ', array_keys(self::HANDLERS)));
            return 1;
        }

        if (!is_readable($path)) {
            $this->error("CSV not readable: {$path}");
            return 1;
        }

        $rows = $this->readCsv($path);
        $this->info("Read " . count($rows) . " rows from {$path}");

        $handler = self::HANDLERS[$type];
        return $this->{$handler}($rows);
    }

    private function readCsv(string $path): array
    {
        $fp = fopen($path, 'r');
        $header = fgetcsv($fp);
        if (!$header) {
            return [];
        }
        $header = array_map(fn($h) => strtolower(trim($h)), $header);

        $rows = [];
        while (($cells = fgetcsv($fp)) !== false) {
            if (count($cells) === 1 && trim($cells[0]) === '') {
                continue; // skip blank lines
            }
            // Pad cells to header length
            $cells = array_pad($cells, count($header), '');
            $rows[] = array_combine($header, array_map('trim', $cells));
        }
        fclose($fp);
        return $rows;
    }

    /**
     * Pairings (simple one-row-per-pair format).
     * Expected columns: production_pattern, match_mode, expected_material_code,
     *                   is_prefix, severity, notes
     */
    private function importPairings(array $rows): int
    {
        $required = ['production_pattern', 'expected_material_code'];
        if (!$this->validateColumns($rows, $required)) return 1;

        $now = now();
        $count = 0;
        foreach ($rows as $row) {
            if ($this->option('dry-run')) {
                $this->line("  WOULD INSERT: {$row['production_pattern']} → {$row['expected_material_code']}");
                continue;
            }
            DB::table('production_material_pairs')->updateOrInsert(
                [
                    'production_pattern'     => $row['production_pattern'],
                    'match_mode'             => $row['match_mode'] ?? 'contains',
                    'expected_material_code' => $row['expected_material_code'],
                    'active'                 => true,
                ],
                [
                    'is_prefix'  => (int)($row['is_prefix'] ?? 0),
                    'severity'   => $row['severity'] ?? 'recommended',
                    'notes'      => $row['notes'] ?? null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
            $count++;
        }
        $this->info("Imported/updated {$count} pairing rows.");
        return 0;
    }

    /**
     * Ceilings.
     * Expected columns: production_pattern, unit, soft_max, hard_max, daily_max, notes
     */
    private function importCeilings(array $rows): int
    {
        if (!$this->validateColumns($rows, ['production_pattern', 'unit'])) return 1;

        $now = now();
        $count = 0;
        foreach ($rows as $row) {
            $payload = [
                'soft_max'   => $this->nullIfEmpty($row['soft_max']  ?? ''),
                'hard_max'   => $this->nullIfEmpty($row['hard_max']  ?? ''),
                'daily_max'  => $this->nullIfEmpty($row['daily_max'] ?? ''),
                'notes'      => $row['notes'] ?? null,
                'updated_at' => $now,
                'created_at' => $now,
            ];
            if ($this->option('dry-run')) {
                $this->line("  WOULD UPSERT: {$row['production_pattern']} ({$row['unit']})");
                continue;
            }
            DB::table('production_qty_limits')->updateOrInsert(
                [
                    'production_pattern' => $row['production_pattern'],
                    'unit'               => $row['unit'],
                    'active'             => true,
                ],
                $payload
            );
            $count++;
        }
        $this->info("Imported/updated {$count} ceiling rows.");
        return 0;
    }

    /**
     * Per-crew-type ratio bands.
     * Expected columns: crew_type_name, metric, lower_bound, upper_bound, notes
     */
    private function importCrewBands(array $rows): int
    {
        if (!$this->validateColumns($rows, ['crew_type_name', 'metric'])) return 1;

        $now = now();
        $count = 0;
        $skipped = 0;
        foreach ($rows as $row) {
            $crewType = DB::table('crew_types')
                ->whereRaw('LOWER(name) = ?', [strtolower($row['crew_type_name'])])
                ->first();
            if (!$crewType) {
                $this->warn("  Skipped: crew_type '{$row['crew_type_name']}' not found");
                $skipped++;
                continue;
            }
            if ($this->option('dry-run')) {
                $this->line("  WOULD UPSERT: {$row['crew_type_name']} / {$row['metric']}");
                continue;
            }
            DB::table('crew_type_ratio_bands')->updateOrInsert(
                [
                    'crew_type_id' => $crewType->id,
                    'metric'       => $row['metric'],
                    'active'       => true,
                ],
                [
                    'lower_bound' => $this->nullIfEmpty($row['lower_bound'] ?? ''),
                    'upper_bound' => $this->nullIfEmpty($row['upper_bound'] ?? ''),
                    'notes'       => $row['notes'] ?? null,
                    'updated_at'  => $now,
                    'created_at'  => $now,
                ]
            );
            $count++;
        }
        $this->info("Imported/updated {$count} crew_type_ratio_bands rows.");
        if ($skipped > 0) {
            $this->warn("{$skipped} rows skipped (unresolved crew_type names).");
        }
        return 0;
    }

    /**
     * Crew × Material × Unit qty limits (Ron's CSV format).
     * Expected columns matching Materials_List.csv:
     *   Crew Type, Material Type, Unit of Measure, Goal..., Soft Ceiling, Hard Ceiling, Notes
     */
    private function importCrewMatQty(array $rows): int
    {
        // Header normalization for Ron's exact format
        $rows = array_map(function ($row) {
            return [
                'crew_type_name'  => $row['crew type'] ?? $row['crew_type_name'] ?? '',
                'material_type'   => $row['material type'] ?? $row['material_type'] ?? '',
                'unit_of_measure' => $row['unit of measure'] ?? $row['unit_of_measure'] ?? '',
                'goal'            => $row['goal for crew to profit company']
                                     ?? $row['goal'] ?? '',
                'soft_max'        => $row['soft ceiling'] ?? $row['soft_max'] ?? '',
                'hard_max'        => $row['hard ceiling'] ?? $row['hard_max'] ?? '',
                'notes'           => $row['notes'] ?? '',
            ];
        }, $rows);

        $now = now();
        $count = 0;
        $skipped = 0;
        foreach ($rows as $row) {
            // Skip rows that are blank or have ? in critical columns
            if (empty($row['crew_type_name']) || empty($row['material_type'])) {
                continue;
            }
            if (str_contains($row['soft_max'], '?') || str_contains($row['hard_max'], '?')) {
                $this->line("  Skipped (uncertain): {$row['crew_type_name']} / {$row['material_type']}");
                $skipped++;
                continue;
            }

            $crewType = DB::table('crew_types')
                ->whereRaw('LOWER(name) = ?', [strtolower($row['crew_type_name'])])
                ->first();
            if (!$crewType) {
                $this->warn("  Skipped: crew_type '{$row['crew_type_name']}' not found");
                $skipped++;
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("  WOULD UPSERT: {$row['crew_type_name']} / {$row['material_type']} / {$row['unit_of_measure']}");
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
                    'goal'       => $this->nullIfEmpty($row['goal']),
                    'soft_max'   => $this->nullIfEmpty($row['soft_max']),
                    'hard_max'   => $this->nullIfEmpty($row['hard_max']),
                    'source'     => 'client_provided',
                    'notes'      => $row['notes'] ?: null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
            $count++;
        }
        $this->info("Imported/updated {$count} crew_type_material_qty_limits rows.");
        if ($skipped > 0) {
            $this->warn("{$skipped} rows skipped (uncertain values, missing crew_type, or blank).");
        }
        return 0;
    }

    private function validateColumns(array $rows, array $required): bool
    {
        if (empty($rows)) {
            $this->error("CSV is empty.");
            return false;
        }
        $headers = array_keys($rows[0]);
        foreach ($required as $col) {
            if (!in_array($col, $headers)) {
                $this->error("CSV missing required column: {$col}");
                return false;
            }
        }
        return true;
    }

    private function nullIfEmpty($value)
    {
        if ($value === '' || $value === null) {
            return null;
        }
        if (is_string($value) && (strtolower(trim($value)) === 'varies' || str_contains($value, '?'))) {
            return null;
        }
        return is_numeric($value) ? (float)$value : $value;
    }
}
