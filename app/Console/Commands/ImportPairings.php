<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Import Ron-confirmed production-to-material pairings from CSV.
 *
 * ─── INPUT FORMAT ───────────────────────────────────────────────────
 * Expects the CSV form of ron_pairings_review.xlsx with columns:
 *
 *   production_description   (string)  e.g. "temp solid 4 white"
 *   sample_size              (int)     card count from discovery
 *   primary_materials        (string)  comma-separated list of materials
 *                                      with confidence percentages, e.g.:
 *                                      "GA WHT PAINT (99.1%), GA BEADS (96.8%)"
 *   borderline_materials     (string)  same format, lower-confidence (ignored)
 *   ron_confirm              (string)  Ron's verdict (see normalization below)
 *   notes                    (string)  Ron's domain commentary (preserved)
 *
 * One row per production pattern. Each row produces N rows in
 * production_material_pairs (one per material in primary_materials).
 *
 * ─── RON_CONFIRM NORMALIZATION ──────────────────────────────────────
 * Ron used multiple spellings. We normalize:
 *
 *   "yes", "correct"           → import as 'required' severity
 *   "partial"                  → SKIP (his prose notes show specific
 *                                materials are wrong; not safe to bulk-import)
 *   "incorrect", "not correct" → SKIP (whole row is wrong)
 *   blank / unrecognized       → SKIP (Ron hasn't gotten to this row yet)
 *
 * Partial and incorrect rows are listed in the summary so we know what
 * needs follow-up review. They are NOT imported in v1 — a future
 * iteration may add structured per-material exclusions for partial rows.
 *
 * ─── XLSX → CSV CONVERSION ──────────────────────────────────────────
 * The source from Ron is an .xlsx file. Convert to CSV first:
 *
 *   # Easiest — open in Excel/LibreOffice, "Save As CSV"
 *   # Or via command line:
 *   libreoffice --headless --convert-to csv ron_pairings_review.xlsx
 *
 * ─── USAGE ──────────────────────────────────────────────────────────
 *   php artisan ai:import-pairings /path/to/ron-pairings.csv
 *   php artisan ai:import-pairings /path/to/ron-pairings.csv --dry-run
 *   php artisan ai:import-pairings /path/to/ron-pairings.csv --truncate
 *
 * ─── IDEMPOTENCY ────────────────────────────────────────────────────
 * Uses updateOrInsert keyed by (production_pattern, match_mode,
 * expected_material_code). Re-running the same CSV is a no-op.
 *
 * Use --truncate to wipe existing rows before import (use when
 * Ron sends an updated/expanded review and you want clean state).
 */
class ImportPairings extends Command
{
    protected $signature = 'ai:import-pairings
                            {csv : Path to Ron-confirmed pairings CSV}
                            {--dry-run : Preview without writing to DB}
                            {--truncate : Wipe production_material_pairs before import}
                            {--severity=required : required | recommended (applied to all imported rows)}
                            {--match-mode=contains : exact | contains | regex (for production_pattern matching)}';

    protected $description = 'Import Ron-confirmed production-material pairings into production_material_pairs';

    /**
     * Map normalized ron_confirm value → action.
     *   import  → write pairs to DB
     *   skip    → log for follow-up review
     */
    private const VERDICT_MAP = [
        'yes'          => 'import',
        'correct'      => 'import',
        'partial'      => 'skip_partial',
        'incorrect'    => 'skip_incorrect',
        'not correct'  => 'skip_incorrect',
    ];

    public function handle(): int
    {
        $path = $this->argument('csv');
        $dryRun = (bool) $this->option('dry-run');
        $truncate = (bool) $this->option('truncate');
        $severity = $this->option('severity');
        $matchMode = $this->option('match-mode');

        if (!is_readable($path)) {
            $this->error("CSV not readable: {$path}");
            return 1;
        }
        if (!in_array($severity, ['required', 'recommended'], true)) {
            $this->error("--severity must be 'required' or 'recommended'");
            return 1;
        }
        if (!in_array($matchMode, ['exact', 'contains', 'regex'], true)) {
            $this->error("--match-mode must be 'exact', 'contains', or 'regex'");
            return 1;
        }

        // Parse the CSV
        $rows = $this->readCsv($path);
        if ($rows === null) return 1;

        $this->info("Read " . count($rows) . " data rows from {$path}");

        // Categorize each row by Ron's verdict
        $categorized = $this->categorizeRows($rows);

        // Print summary BEFORE writing anything
        $this->printSummary($categorized);

        // Optionally truncate
        if ($truncate && !$dryRun) {
            $existing = DB::table('production_material_pairs')->count();
            DB::table('production_material_pairs')->delete();
            $this->warn("Truncated production_material_pairs ({$existing} rows removed).");
        } elseif ($truncate && $dryRun) {
            $this->warn('--truncate noted but skipped (dry-run mode)');
        }

        // Import the "import" rows
        $stats = $this->importRows(
            $categorized['import'],
            $severity,
            $matchMode,
            $dryRun
        );

        // Print final stats + follow-up review list
        $this->printFinalReport($stats, $categorized, $dryRun);

        return 0;
    }

    /**
     * Parse the CSV. Returns array of associative rows keyed by column name,
     * or null on error.
     */
    private function readCsv(string $path): ?array
    {
        $fp = fopen($path, 'r');
        if (!$fp) {
            $this->error("Could not open {$path}");
            return null;
        }

        $header = fgetcsv($fp);
        if ($header === false) {
            $this->error('CSV has no header row');
            fclose($fp);
            return null;
        }

        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header);
        $required = ['production_description', 'primary_materials', 'ron_confirm'];
        foreach ($required as $col) {
            if (!in_array($col, $header, true)) {
                $this->error("CSV missing required column: {$col}");
                $this->line('Found columns: ' . implode(', ', $header));
                fclose($fp);
                return null;
            }
        }

        $rows = [];
        $rowNum = 1;
        while (($data = fgetcsv($fp)) !== false) {
            $rowNum++;
            // Pad short rows so column access doesn't blow up
            while (count($data) < count($header)) {
                $data[] = '';
            }
            $row = array_combine($header, array_slice($data, 0, count($header)));
            $row['_csv_row_num'] = $rowNum;
            $rows[] = $row;
        }
        fclose($fp);
        return $rows;
    }

    /**
     * Categorize rows by Ron's verdict.
     *
     * @return array{
     *   import: array,             // rows to import
     *   skip_partial: array,       // 'partial' verdict — follow-up needed
     *   skip_incorrect: array,     // 'incorrect' verdict — whole pattern wrong
     *   skip_blank: array,         // ron_confirm not yet filled in
     *   skip_unrecognized: array,  // typo or other unrecognized value
     * }
     */
    private function categorizeRows(array $rows): array
    {
        $buckets = [
            'import' => [],
            'skip_partial' => [],
            'skip_incorrect' => [],
            'skip_blank' => [],
            'skip_unrecognized' => [],
        ];

        foreach ($rows as $row) {
            $raw = strtolower(trim((string) ($row['ron_confirm'] ?? '')));

            if ($raw === '') {
                $buckets['skip_blank'][] = $row;
                continue;
            }

            $action = self::VERDICT_MAP[$raw] ?? null;
            if ($action === null) {
                $row['_unrecognized_verdict'] = $raw;
                $buckets['skip_unrecognized'][] = $row;
                continue;
            }

            $buckets[$action === 'import' ? 'import' : $action][] = $row;
        }

        return $buckets;
    }

    /**
     * For each row in the import bucket, parse its primary_materials into
     * individual materials and write one production_material_pairs row each.
     */
    private function importRows(array $rows, string $severity, string $matchMode, bool $dryRun): array
    {
        $stats = [
            'patterns_imported'     => 0,
            'pair_rows_written'     => 0,
            'patterns_with_no_mats' => 0,
            'parse_failures'        => [],
        ];

        foreach ($rows as $row) {
            $pattern = trim((string) $row['production_description']);
            $matsRaw = trim((string) ($row['primary_materials'] ?? ''));
            $note    = trim((string) ($row['notes'] ?? ''));
            $rowNum  = $row['_csv_row_num'];

            if ($pattern === '' || $matsRaw === '') {
                $stats['patterns_with_no_mats']++;
                continue;
            }

            $materials = $this->parseMaterials($matsRaw);
            if (empty($materials)) {
                $stats['parse_failures'][] = "Row {$rowNum}: could not parse any materials from '{$matsRaw}'";
                continue;
            }

            $stats['patterns_imported']++;

            foreach ($materials as $mat) {
                $auditNote = $this->buildNote($note, $mat['confidence']);

                if (!$dryRun) {
                    DB::table('production_material_pairs')->updateOrInsert(
                        [
                            'production_pattern'     => $pattern,
                            'match_mode'             => $matchMode,
                            'expected_material_code' => $mat['name'],
                        ],
                        [
                            'is_prefix' => 0,            // Ron's names are full descriptions, not prefixes
                            'severity'  => $severity,
                            'active'    => 1,
                            'notes'     => $auditNote,
                            'updated_at'=> now(),
                            'created_at'=> now(),
                        ]
                    );
                }

                $stats['pair_rows_written']++;
            }
        }

        return $stats;
    }

    /**
     * Parse a string like "GA WHT PAINT (99.1%), GA BEADS (96.8%)" into:
     *   [ ['name' => 'GA WHT PAINT', 'confidence' => '99.1'],
     *     ['name' => 'GA BEADS',     'confidence' => '96.8'] ]
     *
     * If a confidence percentage is missing, returns the raw name with
     * confidence = ''. Whitespace-only entries are skipped.
     */
    private function parseMaterials(string $raw): array
    {
        $out = [];
        $parts = array_map('trim', explode(',', $raw));
        foreach ($parts as $part) {
            if ($part === '') continue;
            if (preg_match('/^(.+?)\s*\(([\d.]+)%\)\s*$/', $part, $m)) {
                $name = trim($m[1]);
                $conf = $m[2];
            } else {
                $name = $part;
                $conf = '';
            }
            if ($name === '') continue;
            $out[] = ['name' => $name, 'confidence' => $conf];
        }
        return $out;
    }

    private function buildNote(string $ronNote, string $confidence): string
    {
        $parts = [];
        if ($ronNote !== '') {
            $parts[] = 'Ron: ' . $ronNote;
        }
        if ($confidence !== '') {
            $parts[] = "Historical confidence {$confidence}%";
        }
        $parts[] = 'Ron-confirmed 2026-05';
        // production_material_pairs.notes is varchar(500)
        return substr(implode(' | ', $parts), 0, 500);
    }

    private function printSummary(array $cat): void
    {
        $this->newLine();
        $this->info('── Verdict breakdown ──');
        $this->table(
            ['Verdict', 'Patterns', 'Notes'],
            [
                ['yes / correct (will import)',       count($cat['import']),            'imported as required pairs'],
                ['partial (skip — needs follow-up)',  count($cat['skip_partial']),      'Ron flagged some materials wrong'],
                ['incorrect / not correct (skip)',    count($cat['skip_incorrect']),    'whole pattern flagged wrong'],
                ['blank (not yet reviewed)',          count($cat['skip_blank']),        'Ron has not reached these rows'],
                ['unrecognized value',                count($cat['skip_unrecognized']), 'typo? double-check these'],
            ]
        );
    }

    private function printFinalReport(array $stats, array $cat, bool $dryRun): void
    {
        $this->newLine();
        $this->info('── Import results ──');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Patterns imported',                  $stats['patterns_imported']],
                ['Pair rows ' . ($dryRun ? 'would write' : 'written'), $stats['pair_rows_written']],
                ['Patterns with no materials',         $stats['patterns_with_no_mats']],
                ['Parse failures',                     count($stats['parse_failures'])],
            ]
        );

        if (!empty($stats['parse_failures'])) {
            $this->newLine();
            $this->warn('Parse failures:');
            foreach ($stats['parse_failures'] as $msg) {
                $this->line('  - ' . $msg);
            }
        }

        // List the partial / incorrect rows so we know what needs follow-up
        if (!empty($cat['skip_partial'])) {
            $this->newLine();
            $this->warn('PARTIAL rows skipped — need follow-up review:');
            foreach ($cat['skip_partial'] as $row) {
                $note = trim((string) ($row['notes'] ?? ''));
                $this->line(sprintf(
                    '  %s  (sample=%s)',
                    $row['production_description'],
                    $row['sample_size'] ?? '?'
                ));
                if ($note !== '') {
                    $this->line('    note: ' . $note);
                }
            }
        }

        if (!empty($cat['skip_incorrect'])) {
            $this->newLine();
            $this->warn('INCORRECT rows skipped — whole pattern flagged wrong by Ron:');
            foreach ($cat['skip_incorrect'] as $row) {
                $note = trim((string) ($row['notes'] ?? ''));
                $this->line(sprintf(
                    '  %s  (sample=%s)',
                    $row['production_description'],
                    $row['sample_size'] ?? '?'
                ));
                if ($note !== '') {
                    $this->line('    note: ' . $note);
                }
            }
        }

        if (!empty($cat['skip_unrecognized'])) {
            $this->newLine();
            $this->warn('UNRECOGNIZED ron_confirm values — please review:');
            foreach ($cat['skip_unrecognized'] as $row) {
                $this->line(sprintf(
                    "  Row %s: '%s' for pattern '%s'",
                    $row['_csv_row_num'] ?? '?',
                    $row['_unrecognized_verdict'] ?? '?',
                    $row['production_description']
                ));
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('DRY RUN — no rows were written.');
        }

        $this->newLine();
        $this->info('Verify R5 will fire now:');
        $this->line("  SELECT COUNT(*) FROM production_material_pairs WHERE active = 1;");
        $this->line('Then re-score to see effect on band distribution:');
        $this->line('  php artisan ai:score-recent --since=2000-01-01 --rescore --limit=60000');
    }
}
