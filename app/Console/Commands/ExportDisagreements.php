<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Export AI-vs-human disagreement cases to CSV for sample review.
 *
 * THE TWO DISAGREEMENT TYPES
 *   1. AI Red, Human Approved (false positives) — most important
 *      These are cards the AI rejected that a manager approved. Each one
 *      is either: (a) AI being too strict, (b) human being too lenient,
 *      or (c) genuinely defensible either way.
 *
 *   2. AI Green, Human Rejected (false negatives)
 *      Cards the AI passed that a manager rejected. Each one is either:
 *      AI missing a signal it should have caught, OR the human rejecting
 *      for a reason outside the AI's scope (paperwork, signature, billing).
 *
 * OUTPUT FORMAT
 *   CSV with one row per card. Reviewer fills in two columns:
 *     - reviewer_verdict: 'ai_right' | 'ai_wrong' | 'edge_case'
 *     - reviewer_notes:   freeform context (optional but recommended)
 *
 * USAGE
 *   php artisan ai:export-disagreements --case=false_positive --limit=25
 *   php artisan ai:export-disagreements --case=false_negative --limit=25
 *   php artisan ai:export-disagreements --case=both --limit=50
 *
 * WORKFLOW
 *   1. Export → reviewer fills in verdicts → save back to /tmp
 *   2. Run ai:analyze-calibration with the filled CSV
 *   3. Report tells us which rules are overcalling / undercalling
 *   4. Apply tuned thresholds via settings, re-score, re-check
 */
class ExportDisagreements extends Command
{
    protected $signature = 'ai:export-disagreements
                            {--case=false_positive : false_positive | false_negative | both}
                            {--limit=25 : Max cards per case}
                            {--out= : Output path (default: storage/app/disagreements_<timestamp>.csv)}';

    protected $description = 'Export AI-vs-human disagreement cards to CSV for manual review';

    public function handle(): int
    {
        $case = $this->option('case');
        $limit = (int) $this->option('limit');
        $out = $this->option('out') ?: storage_path("app/disagreements_" . now()->format('Ymd_His') . ".csv");

        $rows = [];

        if ($case === 'false_positive' || $case === 'both') {
            $rows = array_merge($rows, $this->fetchFalsePositives($limit));
        }
        if ($case === 'false_negative' || $case === 'both') {
            $rows = array_merge($rows, $this->fetchFalseNegatives($limit));
        }

        if (empty($rows)) {
            $this->warn('No disagreement cases found.');
            return 0;
        }

        $this->writeCsv($out, $rows);

        $this->info("Wrote {$out}");
        $this->info("Rows: " . count($rows));
        $this->newLine();
        $this->line('Next steps:');
        $this->line('  1. Open the CSV in a spreadsheet');
        $this->line('  2. For each row, fill in:');
        $this->line('     - reviewer_verdict: ai_right | ai_wrong | edge_case');
        $this->line('     - reviewer_notes: brief context (optional)');
        $this->line('  3. Save and run: php artisan ai:analyze-calibration <path-to-csv>');

        return 0;
    }

    private function fetchFalsePositives(int $limit): array
    {
        // AI Red, Human Approved
        $rows = DB::table('ai_scoring_audit as asa')
            ->join('jobentries as je', 'je.link', '=', 'asa.link')
            ->leftJoin('users as u', 'u.id', '=', 'je.userId')
            ->leftJoin('crews as c', function ($j) {
                $j->on('c.superintendentId', '=', 'je.userId')
                  ->whereNull('c.deleted_at');
            })
            ->leftJoin('crew_types as ct', 'ct.id', '=', 'c.crew_type_id')
            ->where('asa.band', 'red')
            ->where('je.approved', 1)
            ->whereNotNull('je.approved_date')
            ->select([
                'asa.id as audit_id',
                'asa.link',
                'asa.job_number',
                'asa.workdate',
                'asa.band',
                'asa.score',
                'asa.layer1_findings',
                'je.approved as human_decision',
                'je.approved_date',
                'je.approvedBy',
                'u.name as super_name',
                'ct.name as crew_type',
            ])
            ->inRandomOrder()
            ->limit($limit)
            ->get();

        return $rows->map(fn ($r) => $this->enrichCard((array) $r, 'false_positive'))->toArray();
    }

    private function fetchFalseNegatives(int $limit): array
    {
        // AI Green, Human Rejected
        $rows = DB::table('ai_scoring_audit as asa')
            ->join('jobentries as je', 'je.link', '=', 'asa.link')
            ->leftJoin('users as u', 'u.id', '=', 'je.userId')
            ->leftJoin('crews as c', function ($j) {
                $j->on('c.superintendentId', '=', 'je.userId')
                  ->whereNull('c.deleted_at');
            })
            ->leftJoin('crew_types as ct', 'ct.id', '=', 'c.crew_type_id')
            ->where('asa.band', 'green')
            ->where('je.approved', 2)
            ->select([
                'asa.id as audit_id',
                'asa.link',
                'asa.job_number',
                'asa.workdate',
                'asa.band',
                'asa.score',
                'asa.layer1_findings',
                'je.approved as human_decision',
                'je.approved_date',
                'je.approvedBy',
                'u.name as super_name',
                'ct.name as crew_type',
            ])
            ->inRandomOrder()
            ->limit($limit)
            ->get();

        return $rows->map(fn ($r) => $this->enrichCard((array) $r, 'false_negative'))->toArray();
    }

    /**
     * Add card-level context that helps a human reviewer make sense of the case:
     *  - line item counts and totals
     *  - first finding code (most relevant for false positives)
     */
    private function enrichCard(array $row, string $caseType): array
    {
        $link = $row['link'];

        // Aggregate production
        $prod = DB::table('production')
            ->where('link', $link)
            ->whereNull('deleted_at')
            ->selectRaw('COUNT(*) AS cnt, COALESCE(SUM(qty), 0) AS qty')
            ->first();

        // Aggregate material
        $mat = DB::table('material')
            ->where('link', $link)
            ->whereNull('deleted_at')
            ->selectRaw('COUNT(*) AS cnt, COALESCE(SUM(qty), 0) AS qty')
            ->first();

        // Aggregate equipment
        $equip = DB::table('equipment')
            ->where('link', $link)
            ->whereNull('deleted_at')
            ->selectRaw('COUNT(*) AS cnt, COALESCE(SUM(hours), 0) AS hours')
            ->first();

        // Extract finding codes from layer1_findings JSON
        $findings = json_decode($row['layer1_findings'] ?? '[]', true) ?: [];
        $findingCodes = array_map(fn ($f) => $f['code'] ?? 'unknown', $findings);
        $findingMessages = array_map(fn ($f) => $f['message'] ?? '', $findings);

        return [
            'case_type'        => $caseType,
            'audit_id'         => $row['audit_id'],
            'link'             => $link,
            'job_number'       => $row['job_number'],
            'workdate'         => $row['workdate'],
            'super_name'       => $row['super_name'] ?? '',
            'crew_type'        => $row['crew_type'] ?? '',
            'ai_band'          => $row['band'],
            'ai_score'         => $row['score'],
            'finding_codes'    => implode('; ', $findingCodes),
            'finding_messages' => implode(' | ', $findingMessages),
            'production_lines' => $prod->cnt,
            'production_qty'   => $prod->qty,
            'material_lines'   => $mat->cnt,
            'material_qty'     => $mat->qty,
            'equipment_lines'  => $equip->cnt,
            'equipment_hours'  => $equip->hours,
            'human_decision'   => $row['human_decision'] == 1 ? 'approved' : 'rejected',
            'approved_date'    => $row['approved_date'],
            'approved_by'      => $row['approvedBy'] ?? '',
            'reviewer_verdict' => '',  // reviewer fills in
            'reviewer_notes'   => '',  // reviewer fills in
        ];
    }

    private function writeCsv(string $path, array $rows): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $fh = fopen($path, 'w');
        if (!$fh) {
            throw new \RuntimeException("Could not open {$path} for writing");
        }
        // Header row
        fputcsv($fh, array_keys($rows[0]));
        foreach ($rows as $r) {
            fputcsv($fh, $r);
        }
        fclose($fh);
    }
}
