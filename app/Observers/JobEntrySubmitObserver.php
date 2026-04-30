<?php

namespace App\Observers;

use App\Jobs\Ai\RefreshAiFeatureRowJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Observer for the JobEntry model. Dispatches feature row refresh
 * (and, in Sprint 3+, AI scoring) when a card transitions to submitted.
 *
 * REGISTRATION
 *   In app/Providers/EventServiceProvider.php:
 *     JobEntry::observe(JobEntrySubmitObserver::class);
 *
 * EVENTS HANDLED
 *   updated — when submitted goes from 0 to 1 (initial submit) or when
 *             review_state goes to 'resubmitted' (super fixed and resent)
 *
 * SPRINT 2 BEHAVIOR
 *   Only dispatches the feature refresh job. AI scoring is added in
 *   Sprint 3 (ScoreJobCardJob).
 *
 * SPRINT 3 ADDITION (placeholder, not active yet)
 *   ScoreJobCardJob::dispatch($jobEntry->link)->afterCommit();
 */
class JobEntrySubmitObserver
{
    public function updated($jobEntry): void
    {
        if (!$this->shouldRefresh($jobEntry)) {
            return;
        }

        // Use afterCommit() — we only want to dispatch if the DB transaction
        // actually commits. Otherwise the queue worker can pick up the job
        // before the row is visible and produce stale features.
        RefreshAiFeatureRowJob::dispatch($jobEntry->link)->afterCommit();

        Log::debug('[JobEntrySubmitObserver] feature refresh dispatched', [
            'link' => $jobEntry->link,
            'reason' => $this->refreshReason($jobEntry),
        ]);
    }

    /**
     * Refresh trigger conditions:
     *   1. submitted just changed from 0/null to 1
     *   2. review_state just changed to 'resubmitted'
     */
    private function shouldRefresh($jobEntry): bool
    {
        $changes = $jobEntry->getChanges();

        if (isset($changes['submitted'])
            && $jobEntry->submitted == 1
            && ($jobEntry->getOriginal('submitted') ?? 0) != 1) {
            return true;
        }

        if (isset($changes['review_state'])
            && $jobEntry->review_state === 'resubmitted'
            && $jobEntry->getOriginal('review_state') !== 'resubmitted') {
            return true;
        }

        return false;
    }

    private function refreshReason($jobEntry): string
    {
        $changes = $jobEntry->getChanges();
        if (isset($changes['submitted'])) {
            return 'initial_submit';
        }
        if (isset($changes['review_state'])) {
            return 'resubmitted';
        }
        return 'unknown';
    }
}
