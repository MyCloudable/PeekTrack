<?php

namespace App\Jobs\Ai;

use App\Services\Ai\FeatureBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Refreshes the feature row for a card asynchronously.
 *
 * Dispatched by:
 *   - JobEntrySubmitObserver on card submit/resubmit
 *   - BackfillAiFeatures Artisan command for historical data
 *   - Manual rescore from admin UI (Sprint 5+)
 *
 * Queue: 'ai-features' — separate from 'ai-scoring' so the backfill of
 * historical features doesn't starve real-time scoring during heavy
 * backfills.
 */
class RefreshAiFeatureRowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;
    public int $backoff = 30; // seconds

    public function __construct(
        public string $link
    ) {
        $this->onQueue('ai-features');
    }

    public function handle(FeatureBuilder $builder): void
    {
        try {
            $feature = $builder->buildOrRefresh($this->link);
            Log::debug('[RefreshAiFeature] refreshed', [
                'link' => $this->link,
                'feature_id' => $feature->id,
            ]);
        } catch (Throwable $e) {
            Log::warning('[RefreshAiFeature] failed', [
                'link' => $this->link,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);
            throw $e; // let Laravel's retry mechanism handle backoff
        }
    }

    /**
     * Called when all retries have been exhausted.
     * Logs to failed_jobs (standard Laravel behavior) and emits an alert.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('[RefreshAiFeature] permanently failed after retries', [
            'link' => $this->link,
            'error' => $exception->getMessage(),
        ]);
        // Hook for Sprint 5+ ops alerting:
        // event(new AiFeatureRefreshFailed($this->link, $exception));
    }

    /**
     * Idempotency key for queue deduplication. Multiple submits of the
     * same card within the queue window collapse to a single refresh.
     */
    public function uniqueId(): string
    {
        return "refresh-feature:{$this->link}";
    }
}
