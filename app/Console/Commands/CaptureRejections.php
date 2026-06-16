<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Sprint 4.8 — ai:capture-rejections
 *
 * Polling fallback for JobEntryApprovalObserver. Eloquent observers miss
 * approval changes made via DB::table()->update() or raw SQL. This command
 * scans current state and back-fills any missing rejection/resubmission
 * events into the jobreviews event log.
 *
 * STATE MACHINE (per card)
 *   Card is approved=2 now, and its latest human event is NOT 'rejected'
 *     → insert a 'rejected' event (we just discovered this kickback)
 *
 *   Card is approved=1 now, and its latest human event IS 'rejected'
 *     → insert a 'resubmitted' event (the kickback was corrected)
 *
 * Idempotent: running twice in a row inserts nothing new.
 *
 * SCHEDULING (app/Console/Kernel.php)
 *   $schedule->command('ai:capture-rejections')->everyFifteenMinutes();
 *
 * USAGE
 *   php artisan ai:capture-rejections
 *   php artisan ai:capture-rejections --dry-run
 */
class CaptureRejections extends Command
{
    protected $signature = 'ai:capture-rejections {--dry-run : Show what would be logged without writing}';

    protected $description = 'Back-fill human rejection/resubmission events into the jobreviews event log';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $now = now();

        // ── 1. Cards currently rejected with no open 'rejected' event ──────
        $newRejections = DB::select("
            SELECT je.link, je.job_number, je.approvedBy
            FROM jobentries je
            LEFT JOIN (
                SELECT jr.link, jr.decision
                FROM jobreviews jr
                JOIN (
                    SELECT link, MAX(id) AS max_id
                    FROM jobreviews
                    WHERE reviewed_by_system IS NULL
                    GROUP BY link
                ) latest ON latest.max_id = jr.id
            ) last_event ON last_event.link = je.link
            WHERE je.approved = 2
              AND (last_event.decision IS NULL OR last_event.decision <> 'rejected')
        ");

        // ── 2. Cards re-approved whose latest human event is 'rejected' ────
        $resubmissions = DB::select("
            SELECT je.link, je.job_number, je.approvedBy
            FROM jobentries je
            JOIN (
                SELECT jr.link, jr.decision
                FROM jobreviews jr
                JOIN (
                    SELECT link, MAX(id) AS max_id
                    FROM jobreviews
                    WHERE reviewed_by_system IS NULL
                    GROUP BY link
                ) latest ON latest.max_id = jr.id
            ) last_event ON last_event.link = je.link
            WHERE je.approved = 1
              AND last_event.decision = 'rejected'
        ");

        $this->info('New rejections to log:    ' . count($newRejections));
        $this->info('Resubmissions to log:     ' . count($resubmissions));

        if ($dryRun) {
            foreach ($newRejections as $r) {
                $this->line("  [rejected]    {$r->link}  job {$r->job_number}");
            }
            foreach ($resubmissions as $r) {
                $this->line("  [resubmitted] {$r->link}  job {$r->job_number}");
            }
            $this->warn('DRY RUN — nothing written.');
            return 0;
        }

        $written = 0;

        foreach ($newRejections as $r) {
            DB::table('jobreviews')->insert([
                'link'                => $r->link,
                'job_number'          => $r->job_number ?? '',
                'reviewed_by'         => trim((string) ($r->approvedBy ?? '')) ?: 'human',
                'reviewed_by_user_id' => null,
                'reviewed_by_system'  => null,
                'decision'            => 'rejected',
                'decision_reason'     => 'Kickback detected by ai:capture-rejections poll',
                'date_reviewed'       => $now,
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);
            $written++;
        }

        foreach ($resubmissions as $r) {
            DB::table('jobreviews')->insert([
                'link'                => $r->link,
                'job_number'          => $r->job_number ?? '',
                'reviewed_by'         => trim((string) ($r->approvedBy ?? '')) ?: 'human',
                'reviewed_by_user_id' => null,
                'reviewed_by_system'  => null,
                'decision'            => 'resubmitted',
                'decision_reason'     => 'Post-kickback re-approval detected by ai:capture-rejections poll',
                'date_reviewed'       => $now,
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);
            $written++;
        }

        $this->info("Wrote {$written} event row(s).");
        return 0;
    }
}
