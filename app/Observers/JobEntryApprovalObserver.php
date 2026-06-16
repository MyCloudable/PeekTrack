<?php

namespace App\Observers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sprint 4.8 — Human rejection event logger.
 *
 * WHY THIS EXISTS
 *   jobentries.approved is CURRENT STATE, not history. A rejected card
 *   (approved=2) gets corrected by the super and flips to approved=1,
 *   erasing all evidence the rejection ever happened. That makes it
 *   impossible to measure how often reviewers reject cards — which is
 *   the pilot-gate metric AND the ML training label for Sprint 5.
 *
 *   This observer writes a permanent event row to `jobreviews` every time
 *   a card's approval state transitions. The PeekTrack application does
 *   NOT read or write jobreviews — the AI system owns that table as its
 *   event log (see Developer Overview).
 *
 * EVENTS LOGGED
 *   approved → 2          : decision = 'rejected'    (human kickback)
 *   2 → approved (1)      : decision = 'resubmitted' (corrected + re-approved)
 *
 * REGISTRATION (AppServiceProvider::boot)
 *   \App\Models\JobEntry::observe(\App\Observers\JobEntryApprovalObserver::class);
 *
 * IMPORTANT LIMITATION
 *   Eloquent observers only fire when the model is saved through Eloquent.
 *   If any code path updates jobentries.approved via DB::table()->update()
 *   or raw SQL, this observer will NOT fire. The companion command
 *   ai:capture-rejections (scheduled every 15 min) is the polling fallback
 *   that catches those. Run BOTH — observer for instant capture, poller
 *   for completeness.
 */
class JobEntryApprovalObserver
{
    /**
     * Handle the "updated" event — fires after a JobEntry is saved with changes.
     */
    public function updated($jobEntry): void
    {
        // Only act when the approved column actually changed this save.
        if (!$jobEntry->wasChanged('approved')) {
            return;
        }

        $old = $jobEntry->getOriginal('approved');
        $new = $jobEntry->approved;

        try {
            // Human kickback: any state → 2
            if ((int) $new === 2 && (int) $old !== 2) {
                $this->logEvent($jobEntry, 'rejected', 'Card kicked back by reviewer');
            }

            // Correction cycle complete: 2 → 1
            if ((int) $new === 1 && (int) $old === 2) {
                $this->logEvent($jobEntry, 'resubmitted', 'Card corrected and re-approved after kickback');
            }
        } catch (\Throwable $e) {
            // Never let event logging break the actual approval workflow.
            Log::warning('[JobEntryApprovalObserver] failed to log event', [
                'link' => $jobEntry->link ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function logEvent($jobEntry, string $decision, string $reason): void
    {
        DB::table('jobreviews')->insert([
            'link'                => $jobEntry->link,
            'job_number'          => $jobEntry->job_number ?? '',
            'reviewed_by'         => $this->resolveReviewerName($jobEntry),
            'reviewed_by_user_id' => $this->resolveReviewerId($jobEntry),
            'reviewed_by_system'  => null,   // NULL = human event (AI rows carry a system tag)
            'decision'            => $decision,
            'decision_reason'     => $reason,
            'date_reviewed'       => now(),
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    /**
     * Best-effort reviewer identity. jobentries.approvedBy holds the
     * approver/rejector name in the existing workflow. Falls back to
     * 'human' (column is NOT NULL).
     */
    private function resolveReviewerName($jobEntry): string
    {
        $name = trim((string) ($jobEntry->approvedBy ?? ''));
        return $name !== '' ? $name : 'human';
    }

    private function resolveReviewerId($jobEntry): ?int
    {
        // jobentries has no reviewer user-id column; approvedBy is a name
        // string. Leave NULL unless a future schema change adds one.
        return null;
    }
}
