<?php

namespace App\Console\Commands;

use App\Mail\KickbackEscalation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Escalates cards that have been kicked back for too long without being fixed.
 *
 * RUNS DAILY via Laravel's task scheduler (see Console/Kernel.php).
 *
 * LOGIC
 *   For each card where:
 *     - kicked_back_at IS NOT NULL
 *     - kicked_back_at <= NOW() - {threshold} days
 *     - manager_escalated_at IS NULL  (haven't already escalated)
 *     - approved = 2                  (still rejected, not resubmitted)
 *
 *   1. Look up the super (jobentries.userId → users)
 *   2. Look up the super's manager (users.manager_id → users)
 *   3. If manager exists AND ai.manager_escalation_enabled is true:
 *        → send email to manager
 *        → set manager_escalated_at = NOW()
 *   4. If no manager OR escalation disabled:
 *        → log info, do NOT set manager_escalated_at (so we retry tomorrow
 *          once coverage / setting is fixed)
 *
 * GRACEFUL FAILURE
 *   manager_id coverage was 38.5% at last audit. ~62% of cards will have
 *   no manager to escalate to. This command logs and skips those, doesn't
 *   error out. When ops backfills manager_id and admin flips
 *   ai.manager_escalation_enabled, this command starts escalating those
 *   cards on its next run (no replay needed — manager_escalated_at is
 *   still NULL).
 *
 * THRESHOLD
 *   Default 3 days. Configurable via ai.kickback_escalation_days setting.
 *
 * IDEMPOTENT
 *   Once manager_escalated_at is set, the card is excluded from future runs.
 *   A manager can be escalated at most once per kickback episode. If the
 *   card gets resubmitted then kicked back again, kickback_count increments
 *   and manager_escalated_at clears (TODO: confirm this in KickbackService —
 *   currently it doesn't clear). See known issue note below.
 *
 * KNOWN ISSUE FOR SPRINT 4.1
 *   KickbackService::kickback() does NOT clear manager_escalated_at on the
 *   second kickback of the same card. That means if a super resubmits a
 *   card and the AI rejects it again, the manager won't be escalated again.
 *   Not blocking — first-rejection escalation is the primary use case.
 *   Add to Sprint 4.1 backlog.
 */
class EscalateOverdueKickbacks extends Command
{
    protected $signature = 'ai:escalate-kickbacks
                            {--days= : Override threshold in days (default: setting or 3)}
                            {--dry-run : Print what would be escalated without sending emails}';

    protected $description = 'Escalate kickbacks that have aged past the threshold to managers';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? $this->getThresholdDays());
        $threshold = now()->subDays($days);
        $dryRun = (bool) $this->option('dry-run');
        $enabled = $this->isEscalationEnabled();

        $this->info("Escalation threshold: {$days} days (cards kicked back before {$threshold->toDateTimeString()})");
        $this->info('Escalation enabled: ' . ($enabled ? 'YES' : 'NO (will skip email send, log only)'));
        if ($dryRun) {
            $this->warn('DRY RUN: no emails sent, no rows updated');
        }
        $this->newLine();

        // Find candidate cards. Eager-loading super + manager rows in one
        // query to avoid N+1.
        $candidates = DB::table('jobentries as je')
            ->leftJoin('users as super', 'super.id', '=', 'je.userId')
            ->leftJoin('users as mgr', 'mgr.id', '=', 'super.manager_id')
            ->where('je.approved', 2)
            ->whereNotNull('je.kicked_back_at')
            ->where('je.kicked_back_at', '<=', $threshold)
            ->whereNull('je.manager_escalated_at')
            ->select([
                'je.link',
                'je.job_number',
                'je.kicked_back_at',
                'je.kickback_count',
                'je.userId as super_id',
                'super.name as super_name',
                'super.email as super_email',
                'super.manager_id',
                'mgr.id as manager_id_resolved',
                'mgr.name as manager_name',
                'mgr.email as manager_email',
            ])
            ->get();

        $this->info("Found {$candidates->count()} overdue kickback(s).");

        if ($candidates->isEmpty()) {
            return 0;
        }

        $escalated = 0;
        $skippedNoManager = 0;
        $skippedDisabled = 0;
        $failed = 0;

        foreach ($candidates as $card) {
            $hasManager = !empty($card->manager_id_resolved) && !empty($card->manager_email);

            if (!$hasManager) {
                $skippedNoManager++;
                Log::info('[EscalateOverdueKickbacks] skipped — no manager assigned or manager has no email', [
                    'link'        => $card->link,
                    'super_id'    => $card->super_id,
                    'manager_id'  => $card->manager_id_resolved,
                    'has_email'   => !empty($card->manager_email),
                ]);
                continue;
            }

            if (!$enabled) {
                $skippedDisabled++;
                Log::info('[EscalateOverdueKickbacks] skipped — escalation feature disabled', [
                    'link' => $card->link,
                ]);
                continue;
            }

            if ($dryRun) {
                $this->line(sprintf(
                    '  WOULD ESCALATE: %s (job %s) to %s <%s>',
                    $card->link,
                    $card->job_number,
                    $card->manager_name,
                    $card->manager_email
                ));
                continue;
            }

            try {
                Mail::to($card->manager_email)->send(new KickbackEscalation([
                    'manager_name'    => $card->manager_name,
                    'super_name'      => $card->super_name,
                    'super_email'     => $card->super_email,
                    'job_number'      => $card->job_number,
                    'card_link'       => $card->link,
                    'kicked_back_at'  => $card->kicked_back_at,
                    'kickback_count'  => $card->kickback_count,
                    'days_overdue'    => $days,
                ]));

                DB::table('jobentries')
                    ->where('link', $card->link)
                    ->update([
                        'manager_escalated_at' => now(),
                        'updated_at'           => now(),
                    ]);

                $escalated++;
                Log::info('[EscalateOverdueKickbacks] escalated', [
                    'link'         => $card->link,
                    'manager_id'   => $card->manager_id_resolved,
                ]);
            } catch (Throwable $e) {
                $failed++;
                Log::error('[EscalateOverdueKickbacks] escalation failed', [
                    'link'  => $card->link,
                    'error' => $e->getMessage(),
                ]);
                $this->error("  FAILED: {$card->link} — {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->table(
            ['Status', 'Count'],
            [
                ['Found candidates',          $candidates->count()],
                ['Escalated (email sent)',    $escalated],
                ['Skipped (no manager)',      $skippedNoManager],
                ['Skipped (feature off)',     $skippedDisabled],
                ['Failed',                    $failed],
            ]
        );

        return $failed > 0 ? 1 : 0;
    }

    private function getThresholdDays(): int
    {
        $val = DB::table('settings')
            ->where('key_name', 'ai.kickback_escalation_days')
            ->value('value');
        return $val !== null ? (int) $val : 3;
    }

    private function isEscalationEnabled(): bool
    {
        $val = DB::table('settings')
            ->where('key_name', 'ai.manager_escalation_enabled')
            ->value('value');
        if ($val === null) return false;
        return in_array(strtolower(trim((string) $val)), ['true', '1', 'yes'], true);
    }
}
