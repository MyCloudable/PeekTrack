<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Finder\Finder;

/**
 * SPRINT 1 TICKET S1-07: Audit the codebase and database for all uses of
 * the `materials` (plural) table, which is deprecated. Produces a report
 * the team uses to remove references before S1-08 renames the table.
 *
 * Usage:
 *   php artisan ai:audit-materials
 *   php artisan ai:audit-materials --path=app
 *
 * Output:
 *   - Row counts and last-write timestamps for `material` vs `materials`
 *   - Orphan analysis (rows in `materials` with no corresponding link in `material`)
 *   - Grep across PHP, Blade, JS, and Vue files for references to 'materials'
 *   - CSV written to storage/app/materials-audit-<timestamp>.csv
 *
 * Exit codes:
 *   0 - clean (no references outside this audit command and migrations)
 *   1 - references found in application code that must be removed
 */
class AuditMaterialsUsage extends Command
{
    protected $signature = 'ai:audit-materials
                            {--path=* : Only search these paths (default: app, resources, database/seeders)}
                            {--fail-on-refs : Exit non-zero if any code references are found}';

    protected $description = 'Audit codebase and DB for usage of the deprecated `materials` (plural) table.';

    /**
     * Patterns that indicate a write or read to the deprecated `materials` table.
     * We purposely don't match 'material' alone because that's the canonical table.
     * The word boundary and quote context avoid matching words like
     * 'rawMaterials', 'material_type', etc.
     */
    private array $patterns = [
        "DB::table\\(['\"]materials['\"]\\)",
        "->from\\(['\"]materials['\"]\\)",
        "Schema::(?:create|table|drop|rename)\\(['\"]materials['\"]",
        "'table'\\s*=>\\s*['\"]materials['\"]",
        "class\\s+Materials\\s+extends\\s+Model",
        "protected\\s+\\\$table\\s*=\\s*['\"]materials['\"]",
        "FROM\\s+materials(\\s|\\b)",
        "INTO\\s+materials(\\s|\\b)",
        "UPDATE\\s+materials(\\s|\\b)",
    ];

    public function handle(): int
    {
        $this->info('── PeekTrack AI · Materials table audit ──');
        $this->newLine();

        $hasErrors = false;

        // ── Section 1: Database row counts ────────────────────────────
        $this->section('1. Database row counts');

        $material   = (int) DB::table('material')->count();
        $materials  = (int) DB::table('materials')->count();

        $lastMaterial  = DB::table('material')->max('created_at');
        $lastMaterials = DB::table('materials')->max('created_at');

        $this->table(
            ['Table', 'Rows', 'Last created_at'],
            [
                ['material  (canonical)', number_format($material),  $lastMaterial  ?? 'never'],
                ['materials (deprecated)', number_format($materials), $lastMaterials ?? 'never'],
            ]
        );

        // Flag if materials had recent writes
        if ($lastMaterials !== null) {
            $daysSince = now()->diffInDays($lastMaterials);
            if ($daysSince < 90) {
                $this->warn("⚠  materials (plural) had a write {$daysSince} days ago.");
                $this->warn('  There is active code writing to the deprecated table.');
                $hasErrors = true;
            }
        }

        // ── Section 2: Orphan analysis ────────────────────────────────
        $this->section('2. Orphan analysis (rows in materials not represented in material)');

        if ($materials > 0) {
            $orphans = DB::select("
                SELECT COUNT(*) AS n
                FROM materials m
                LEFT JOIN material mm
                    ON mm.link = m.link
                   AND mm.description = m.description
                WHERE mm.id IS NULL
            ")[0]->n ?? 0;

            $this->line("  Orphans (rows in materials with no match in material): {$orphans}");
            if ($orphans > 0) {
                $this->warn("  These rows would be LOST if the materials table is dropped without migration.");
                $this->warn('  Run ai:migrate-materials (S1-08) to bring them forward first.');
            }
        } else {
            $this->line('  materials is empty — nothing to migrate.');
        }

        // ── Section 3: Codebase grep ──────────────────────────────────
        $this->section('3. Codebase references to `materials`');

        $paths = $this->option('path') ?: ['app', 'resources', 'database/seeders'];
        $extensions = ['php', 'blade.php', 'vue', 'js', 'ts'];

        $finder = new Finder();
        $finder->files()
            ->in(array_map(fn($p) => base_path($p), $paths))
            ->name('/\.(php|vue|js|ts)$/')
            ->notName('/AuditMaterialsUsage\.php$/')    // exclude this file
            ->notPath('/database\/migrations/');         // exclude migrations

        $hits = [];
        $combined = '/(' . implode('|', $this->patterns) . ')/i';

        foreach ($finder as $file) {
            $contents = $file->getContents();
            if (!preg_match($combined, $contents)) {
                continue;
            }

            $lineNumber = 0;
            foreach (explode("\n", $contents) as $line) {
                $lineNumber++;
                if (preg_match($combined, $line, $m)) {
                    $hits[] = [
                        'file' => str_replace(base_path() . '/', '', $file->getPathname()),
                        'line' => $lineNumber,
                        'match' => trim($line),
                        'pattern' => $m[1] ?? '',
                    ];
                }
            }
        }

        if (count($hits) === 0) {
            $this->info('  ✓ No references to `materials` in application code.');
        } else {
            $this->warn('  ✗ Found ' . count($hits) . ' reference(s):');
            foreach ($hits as $hit) {
                $this->line("    {$hit['file']}:{$hit['line']}");
                $this->line("      └ " . substr($hit['match'], 0, 120));
            }
            $hasErrors = true;
        }

        // ── Section 4: Write CSV ──────────────────────────────────────
        $this->section('4. Full report');

        $csvPath = storage_path('app/materials-audit-' . date('Ymd-His') . '.csv');
        $fp = fopen($csvPath, 'w');
        fputcsv($fp, ['file', 'line', 'pattern', 'match']);
        foreach ($hits as $hit) {
            fputcsv($fp, [$hit['file'], $hit['line'], $hit['pattern'], $hit['match']]);
        }
        fclose($fp);

        $this->info("  Written to: {$csvPath}");
        $this->newLine();

        // ── Section 5: Exit ───────────────────────────────────────────
        if ($hasErrors) {
            $this->error('Audit found issues that must be resolved before running the rename migration.');
            return $this->option('fail-on-refs') ? 1 : 0;
        }

        $this->info('✓ Audit clean — safe to proceed with S1-08 (rename materials → materials_deprecated).');
        return 0;
    }

    private function section(string $title): void
    {
        $this->newLine();
        $this->line('<fg=cyan>' . $title . '</>');
        $this->line(str_repeat('─', strlen($title)));
    }
}
