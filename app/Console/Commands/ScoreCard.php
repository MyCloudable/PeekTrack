<?php

namespace App\Console\Commands;

use App\Models\JobCardAiFeature;
use App\Services\Ai\AiDecisionMaker;
use Illuminate\Console\Command;

/**
 * Score a single card by link UUID. Outputs the full decision pipeline
 * result for spot-debugging.
 *
 *   php artisan ai:score-card <link>
 *   php artisan ai:score-card <link> --no-persist
 *     (prints decision but skips ai_scoring_audit / jobreviews writes)
 */
class ScoreCard extends Command
{
    protected $signature = 'ai:score-card
                            {link : jobentries.link UUID}
                            {--no-persist : Compute decision but do not write to audit / jobreviews}';

    protected $description = 'Score a single card and print the decision (debug tool)';

    public function handle(AiDecisionMaker $decisionMaker): int
    {
        $link = $this->argument('link');

        $feature = JobCardAiFeature::where('link', $link)->first();
        if (!$feature) {
            $this->error("No feature row found for link {$link}.");
            $this->line("Run: php artisan ai:backfill-features --link={$link}");
            return 1;
        }

        // --no-persist: stub out the persistence-bound DB tables
        if ($this->option('no-persist')) {
            $this->warn('--no-persist: decision will not be saved');
        }

        $decision = $decisionMaker->decide($feature);

        $this->newLine();
        $this->info("=== AI Decision: {$link} ===");
        $this->line("Band:    {$decision['band']}");
        $this->line("Score:   {$decision['score']}");
        $this->line("Shadow:  " . ($decision['shadow'] ? 'YES' : 'no'));
        $this->line("Audit:   #{$decision['audit_id']}");
        $this->newLine();

        if (empty($decision['findings'])) {
            $this->info('No findings.');
            return 0;
        }

        $this->line('--- Findings ---');
        foreach ($decision['findings'] as $f) {
            $this->line(sprintf(
                '[%s] %s: %s',
                $f['rule_id'],
                $f['severity'],
                $f['message']
            ));
        }

        return 0;
    }
}
