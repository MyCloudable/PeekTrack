<?php

namespace App\Console\Commands;

use App\Jobs\Ai\RefreshAiFeatureRowJob;
use App\Services\Ai\FeatureBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Backfills feature rows for historical job cards.
 *
 * Run modes:
 *
 *   php artisan ai:backfill-features
 *       Default. Backfills last 2 years of approved cards inline (no queue).
 *       Best for first-time backfill on staging.
 *
 *   php artisan ai:backfill-features --queue
 *       Dispatches one RefreshAiFeatureRowJob per card. Use this on prod
 *       so the backfill doesn't lock the request thread. Will take longer
 *       wall-clock but won't impact app responsiveness.
 *
 *   php artisan ai:backfill-features --years=1
 *       Limit lookback. Default 2 years.
 *
 *   php artisan ai:backfill-features --since=2025-01-01
 *       Backfill from a specific date forward (overrides --years).
 *
 *   php artisan ai:backfill-features --link=<uuid>
 *       Backfill a single card. Useful for spot-debugging.
 *
 *   php artisan ai:backfill-features --dry-run
 *       Print the count and a sample of cards that would be processed
 *       but don't actually refresh anything.
 *
 *   php artisan ai:backfill-features --force-rebuild
 *       Re-run aggregation for cards that already have feature rows.
 *       Default is to skip cards with existing features (idempotent).
 *
 * PERFORMANCE
 *   Inline mode processes ~150-300 cards/sec on staging. For prod with
 *   ~50K-200K historical cards, expect 5-25 minutes. Queue mode is
 *   slower wall-clock but is concurrent-safe.
 */
class BackfillAiFeatures extends Command
{
    protected $signature = 'ai:backfill-features
                            {--queue : Dispatch via queue instead of inline}
                            {--years=2 : Lookback years from today}
                            {--since= : Backfill from specific date (YYYY-MM-DD)}
                            {--link= : Backfill a single card by link UUID}
                            {--dry-run : Show what would be processed without doing it}
                            {--force-rebuild : Rebuild rows that already exist}';

    protected $description = 'Backfill jobcard_ai_features rows for historical cards';

    public function handle(FeatureBuilder $builder): int
    {
        $cards = $this->buildCardQuery();
        $total = $cards->count();

        $this->info("Found {$total} cards to process.");

        if ($total === 0) {
            return 0;
        }

        if ($this->option('dry-run')) {
            $sample = $cards->limit(5)->pluck('link', 'workdate');
            $this->info("Sample (first 5):");
            foreach ($sample as $date => $link) {
                $this->line("  {$date}  {$link}");
            }
            $this->warn("DRY RUN — no rows written.");
            return 0;
        }

        $useQueue = $this->option('queue');
        $progress = $this->output->createProgressBar($total);
        $progress->start();

        $processed = 0;
        $failed = 0;
        $skipped = 0;

        // chunkById iterates by jobentries.id (insertion order, roughly
        // chronological). It's safe to use here because we're only
        // inserting/updating jobcard_ai_features rows, not modifying
        // jobentries during iteration.
        $cards->chunkById(500, function ($chunk) use (
            $builder, $useQueue, $progress, &$processed, &$failed, &$skipped
        ) {
            foreach ($chunk as $card) {
                try {
                    if (!$this->option('force-rebuild') && $this->alreadyHasFeature($card->link)) {
                        $skipped++;
                        $progress->advance();
                        continue;
                    }

                    if ($useQueue) {
                        RefreshAiFeatureRowJob::dispatch($card->link);
                    } else {
                        $builder->buildOrRefresh($card->link);
                    }
                    $processed++;
                } catch (\Throwable $e) {
                    Log::warning('[BackfillAiFeatures] failed', [
                        'link' => $card->link,
                        'error' => $e->getMessage(),
                    ]);
                    $failed++;
                }
                $progress->advance();
            }
        });

        $progress->finish();
        $this->newLine(2);

        $this->info('── Backfill complete ──');
        $this->table(
            ['Status', 'Count'],
            [
                ['Total cards in window',   $total],
                ['Processed',               $processed],
                ['Skipped (already built)', $skipped],
                ['Failed',                  $failed],
            ]
        );

        if ($useQueue) {
            $this->info("Jobs dispatched to queue 'ai-features'. Run a worker to process them:");
            $this->line("  php artisan queue:work --queue=ai-features");
        }

        if ($failed > 0) {
            $this->warn("Some cards failed — see storage/logs/laravel.log for details.");
            return 1;
        }

        return 0;
    }

    private function buildCardQuery()
    {
        $query = DB::table('jobentries')->where('submitted', 1);

        if ($this->option('link')) {
            return $query->where('link', $this->option('link'));
        }

        if ($since = $this->option('since')) {
            $query->where('workdate', '>=', $since);
        } else {
            $years = (int) $this->option('years');
            $query->where('workdate', '>=', now()->subYears($years));
        }

        return $query;
    }

    private function alreadyHasFeature(string $link): bool
    {
        return DB::table('jobcard_ai_features')
            ->where('link', $link)
            ->exists();
    }
}
