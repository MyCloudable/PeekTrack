<?php

namespace App\Console\Commands;

use App\Models\JobCardAiFeature;
use App\Services\Ai\AiDecisionMaker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Score all recent cards in shadow mode. Used during the Week-4 shadow
 * mode period to build up audit data before pilot.
 *
 *   php artisan ai:score-recent              # last 1 day (default)
 *   php artisan ai:score-recent --days=7
 *   php artisan ai:score-recent --since=2026-04-01
 *   php artisan ai:score-recent --rescore    # re-score cards that
 *                                             # already have an audit
 *
 * Performance note: this is a single-process batch. Each card takes
 * ~10-50ms with all rules. 1,000 cards = ~30 seconds.
 */
class ScoreRecent extends Command
{
    protected $signature = 'ai:score-recent
                            {--days=1 : Lookback days (ignored if --since)}
                            {--since= : Score cards from this date onward (YYYY-MM-DD)}
                            {--rescore : Re-score cards that already have audit rows}
                            {--limit=10000 : Hard cap on rows to process}';

    protected $description = 'Score recent cards (shadow mode) and write audit rows';

    public function handle(AiDecisionMaker $maker): int
    {
        $since = $this->option('since')
            ?: now()->subDays((int) $this->option('days'))->toDateString();
        $rescore = (bool) $this->option('rescore');
        $limit = max(1, (int) $this->option('limit'));

        $q = JobCardAiFeature::query()
            ->whereDate('workdate', '>=', $since);

        if (!$rescore) {
            // Skip cards that already have an audit row
            $q->whereNotIn('link', function ($sub) {
                $sub->select('link')->from('ai_scoring_audit');
            });
        }

        // Important:
        // chunkById() does not safely respect a limit applied on the original query.
        // So first we select the exact limited IDs, then process only those IDs.
        $ids = (clone $q)
        ->orderBy('id')
        ->limit($limit)
        ->pluck('id');

        $total = $ids->count();

        if ($total === 0) {
            $this->info("No cards to score (since {$since}, rescore={$rescore}).");
            return 0;
        }

        $this->info("Scoring {$total} cards (since {$since})...");
        $bar = $this->output->createProgressBar($total);

        $bandCounts = ['green' => 0, 'yellow' => 0, 'red' => 0];
        $errors = 0;

        JobCardAiFeature::query()
        ->whereIn('id', $ids)
        ->orderBy('id')
        ->chunkById(200, function ($chunk) use ($maker, $bar, &$bandCounts, &$errors) {
            foreach ($chunk as $feature) {
                try {
                    $decision = $maker->decide($feature, false);
                    $bandCounts[$decision['band']]++;
                } catch (\Throwable $e) {
                    $errors++;
                    $this->newLine();
                    $this->error("Error on {$feature->link}: {$e->getMessage()}");
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info('=== Shadow scoring complete ===');
        $this->table(
            ['Band', 'Count'],
            [
                ['Green',  $bandCounts['green']],
                ['Yellow', $bandCounts['yellow']],
                ['Red',    $bandCounts['red']],
                ['Errors', $errors],
            ]
        );

        return $errors > 0 ? 1 : 0;
    }
}
