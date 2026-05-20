<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Read a reviewed disagreements CSV and produce a calibration report.
 *
 * For each finding code (R1, R2, R3, R8...), tells us:
 *   - How often the reviewer said "ai_right" (rule fired correctly)
 *   - How often the reviewer said "ai_wrong" (rule overcalled)
 *   - How often "edge_case"
 *
 * Recommended action per rule:
 *   - precision > 80%: keep as hard rule
 *   - precision 50-80%: convert to soft rule (HIGH severity, configurable threshold)
 *   - precision < 50%: convert to soft rule (MED severity) or remove
 *
 * USAGE
 *   php artisan ai:analyze-calibration /tmp/disagreements_reviewed.csv
 */
class AnalyzeCalibration extends Command
{
    protected $signature = 'ai:analyze-calibration {csv_path : Path to reviewed CSV from ai:export-disagreements}';

    protected $description = 'Read a reviewed disagreements CSV and report per-rule precision';

    public function handle(): int
    {
        $path = $this->argument('csv_path');
        if (!file_exists($path)) {
            $this->error("File not found: {$path}");
            return 1;
        }

        $rows = $this->readCsv($path);
        if (empty($rows)) {
            $this->error("No rows in CSV.");
            return 1;
        }

        // Validate the CSV has the expected columns
        $required = ['case_type', 'finding_codes', 'reviewer_verdict'];
        $missing = array_diff($required, array_keys($rows[0]));
        if (!empty($missing)) {
            $this->error('CSV missing required columns: ' . implode(', ', $missing));
            return 1;
        }

        // Filter to only rows the reviewer actually marked
        $reviewed = array_filter($rows, fn ($r) => !empty(trim($r['reviewer_verdict'] ?? '')));

        $this->info("Total rows: " . count($rows));
        $this->info("Reviewed:   " . count($reviewed));
        if (count($reviewed) < count($rows)) {
            $this->warn("Skipping " . (count($rows) - count($reviewed)) . " unmarked rows");
        }
        $this->newLine();

        // Split by case type
        $byCase = ['false_positive' => [], 'false_negative' => []];
        foreach ($reviewed as $r) {
            $type = $r['case_type'] ?? 'false_positive';
            $byCase[$type][] = $r;
        }

        if (!empty($byCase['false_positive'])) {
            $this->reportFalsePositives($byCase['false_positive']);
        }
        if (!empty($byCase['false_negative'])) {
            $this->reportFalseNegatives($byCase['false_negative']);
        }

        $this->printRecommendations();
        return 0;
    }

    private function reportFalsePositives(array $rows): void
    {
        $this->info("=== FALSE POSITIVES (AI Red, Human Approved) ===");
        $this->line("Question: when AI rejected, was AI right?");
        $this->newLine();

        // Aggregate by finding code
        $byCode = []; // code => ['ai_right', 'ai_wrong', 'edge_case']
        foreach ($rows as $r) {
            $verdict = strtolower(trim($r['reviewer_verdict']));
            $codes = array_filter(array_map('trim', explode(';', $r['finding_codes'] ?? '')));
            foreach ($codes as $code) {
                if (!isset($byCode[$code])) {
                    $byCode[$code] = ['ai_right' => 0, 'ai_wrong' => 0, 'edge_case' => 0];
                }
                if (isset($byCode[$code][$verdict])) {
                    $byCode[$code][$verdict]++;
                }
            }
        }

        $tableRows = [];
        foreach ($byCode as $code => $tally) {
            $total = $tally['ai_right'] + $tally['ai_wrong'] + $tally['edge_case'];
            $precision = $total > 0 ? round(100 * $tally['ai_right'] / $total, 1) : 0;
            $recommendation = $this->recommendForCode($precision);
            $tableRows[] = [
                $code,
                $total,
                $tally['ai_right'],
                $tally['ai_wrong'],
                $tally['edge_case'],
                "{$precision}%",
                $recommendation,
            ];
        }

        $this->table(
            ['Code', 'N', 'AI right', 'AI wrong', 'Edge', 'Precision', 'Recommendation'],
            $tableRows
        );
        $this->newLine();
    }

    private function reportFalseNegatives(array $rows): void
    {
        $this->info("=== FALSE NEGATIVES (AI Green, Human Rejected) ===");
        $this->line("Question: should AI have caught these, or were they out-of-scope?");
        $this->newLine();

        // For false negatives we care about the rejection REASON the human gave (which we don't have
        // structured access to). Best we can do: just summarize verdicts and notes.
        $tally = ['ai_right' => 0, 'ai_wrong' => 0, 'edge_case' => 0];
        foreach ($rows as $r) {
            $v = strtolower(trim($r['reviewer_verdict']));
            if (isset($tally[$v])) {
                $tally[$v]++;
            }
        }
        $total = array_sum($tally);

        $this->table(
            ['Verdict', 'Count', '%'],
            [
                ['AI should have caught (ai_wrong)', $tally['ai_wrong'], $total ? round(100 * $tally['ai_wrong'] / $total, 1) . '%' : '0%'],
                ['AI was right to let it pass (ai_right)', $tally['ai_right'], $total ? round(100 * $tally['ai_right'] / $total, 1) . '%' : '0%'],
                ['Edge case', $tally['edge_case'], $total ? round(100 * $tally['edge_case'] / $total, 1) . '%' : '0%'],
            ]
        );

        if ($tally['ai_wrong'] > 0) {
            $this->newLine();
            $this->line('Cards where AI should have caught — recommend reviewing reviewer_notes:');
            foreach ($rows as $r) {
                if (strtolower(trim($r['reviewer_verdict'])) === 'ai_wrong') {
                    $this->line(sprintf(
                        '  %s (job %s): %s',
                        $r['link'],
                        $r['job_number'],
                        $r['reviewer_notes'] ?? '(no notes)'
                    ));
                }
            }
        }
        $this->newLine();
    }

    private function recommendForCode(float $precision): string
    {
        if ($precision >= 80) return 'Keep as hard rule';
        if ($precision >= 50) return 'Convert to soft (HIGH)';
        if ($precision >= 20) return 'Soft (MED) + threshold tuning';
        return 'Disable or radical rethink';
    }

    private function printRecommendations(): void
    {
        $this->info("=== NEXT STEPS ===");
        $this->line(' 1. For rules with precision <80%, adjust thresholds in settings:');
        $this->line('    UPDATE settings SET value = ... WHERE key_name = ai.rule_threshold.R1_material_min_qty;');
        $this->line(' 2. For rules with precision <50%, consider demoting from HARD to HIGH severity');
        $this->line(' 3. Re-score: php artisan ai:score-recent --since=2000-01-01 --rescore --limit=60000');
        $this->line(' 4. Re-check agreement: SELECT band, COUNT(*) FROM ai_scoring_audit GROUP BY band');
    }

    private function readCsv(string $path): array
    {
        $rows = [];
        $fh = fopen($path, 'r');
        if (!$fh) return [];

        $headers = fgetcsv($fh);
        if (!$headers) {
            fclose($fh);
            return [];
        }

        while (($data = fgetcsv($fh)) !== false) {
            if (count($data) !== count($headers)) continue;
            $rows[] = array_combine($headers, $data);
        }
        fclose($fh);
        return $rows;
    }
}
