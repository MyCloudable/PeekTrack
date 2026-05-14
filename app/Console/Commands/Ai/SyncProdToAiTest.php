<?php

namespace App\Console\Commands\Ai;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


/**
 * Sync selected PeekTrack source/business tables into the AI test database.
 *
 * Why this command exists:
 * - Production does not have the AI sprint output tables yet.
 * - We need real production-like source data in peektrack_ai_test before Sprint 4.
 * - After source data is synced, AI-generated tables should be rebuilt using:
 *      php -d memory_limit=1024M artisan ai:backfill-features --force-rebuild
 *      php artisan ai:score-recent --since=2020-01-01 --limit=10000 --rescore
 *
 * Important rules:
 * - This command copies only source/business tables used by AI code.
 * - AI config/rule tables are NOT copied from production for now.
 * - AI output tables are NOT copied from production. They can be reset and rebuilt.
 * - Copy uses only common columns between source and target, because production may not
 *   have new AI columns that exist in peektrack_ai_test.
 * - Source and target DB names must be different, otherwise command stops.
 *
 * Main local use:
 *      php artisan ai:sync-prod-to-ai-test --dry-run
 *      php artisan ai:sync-prod-to-ai-test --fresh
 *      php artisan ai:sync-prod-to-ai-test --reset-ai-output
 *
 * Main staging/server use later:
 *      php artisan ai:sync-prod-to-ai-test --fresh --reset-ai-output
 * 
 * Full refresh use:
 *      php -d memory_limit=1024M artisan ai:sync-prod-to-ai-test --fresh --reset-ai-output --rebuild-ai --score-ai
 */

class SyncProdToAiTest extends Command
{
    
    protected $signature = 'ai:sync-prod-to-ai-test
        {--dry-run : Show counts and column differences only, do not write}
        {--fresh : Truncate target source tables before copying}
        {--reset-ai-output : Truncate AI generated output tables}
        {--rebuild-ai : Run ai:backfill-features --force-rebuild after sync/reset}
        {--score-ai : Run ai:score-recent after rebuild}
        {--table=* : Sync only selected source table(s)}
        {--limit= : Optional max rows per table for testing}';


    protected $description = 'Sync selected production/source tables into the AI test database.';

    /**
     * Existing PeekTrack source/business tables used by AI code.
     *
     * Order matters:
     * - Lookup-ish tables first: crew_types, crews
     * - Main parent cards next: jobentries
     * - Related/detail tables after that
     * - jobreviews last because AI scoring can also append to this table later
     */
    private array $sourceTables = [
        'crew_types',
        'crews',
        'jobentries',
        'job_data',
        'production',
        'material',
        'equipment',
        'jobreviews',
    ];

    /**
     * AI config/rule tables created by AI migrations/imports.
     * These are NOT copied from production for now.
     */
    private array $aiConfigTables = [
        'settings',
        'production_material_pairs',
        'production_qty_limits',
        'crew_type_ratio_bands',
        'crew_type_material_qty_limits',
    ];

    /**
     * AI generated output tables.
     * These are rebuilt by AI commands.
     */
    private array $aiOutputTables = [
        'jobcard_ai_features',
        'ai_scoring_audit',
    ];


    public function handle(): int
    {
        $this->info('AI prod/source to AI test DB sync');

        // These connection names come from config/database.php.
        $source = DB::connection('ai_sync_source');
        $target = DB::connection('ai_sync_target');

        $sourceDb = $source->getDatabaseName();
        $targetDb = $target->getDatabaseName();

        $this->line("Source DB: {$sourceDb}");
        $this->line("Target DB: {$targetDb}");

        // Safety guard: never allow this command to run if both DB names are same.
        if ($sourceDb === $targetDb) {
            $this->error('Source and target database names are the same. Stopping for safety.');
            return self::FAILURE;
        }

        $selectedTables = $this->selectedSourceTables();

        if (empty($selectedTables)) {
            return self::FAILURE;
        }

        $this->newLine();

        $this->info('Source/business tables selected for sync:');
        foreach ($selectedTables as $table) {
            $this->line(" - {$table}");
        }

        $this->newLine();

        $this->info('AI config/rule tables will NOT be copied from source:');
        foreach ($this->aiConfigTables as $table) {
            $this->line(" - {$table}");
        }

        $this->newLine();

        $this->info('AI output tables will NOT be copied from source:');
        foreach ($this->aiOutputTables as $table) {
            $this->line(" - {$table}");
        }

        $this->newLine();

        $this->line('dry-run: ' . ($this->option('dry-run') ? 'yes' : 'no'));
        $this->line('fresh: ' . ($this->option('fresh') ? 'yes' : 'no'));
        $this->line('reset-ai-output: ' . ($this->option('reset-ai-output') ? 'yes' : 'no'));
        $this->line('rebuild-ai: ' . ($this->option('rebuild-ai') ? 'yes' : 'no'));
        $this->line('score-ai: ' . ($this->option('score-ai') ? 'yes' : 'no'));
        $this->line('limit: ' . ($this->option('limit') ?: 'none'));

        $this->newLine();

        if ($this->option('dry-run')) {
            $this->runDryRun($selectedTables);

            return self::SUCCESS;
        }

        // Safety: do not allow accidental write mode without an explicit action.
        if (
            !$this->option('fresh')
            && !$this->option('reset-ai-output')
            && !$this->option('rebuild-ai')
            && !$this->option('score-ai')
        ) {
            $this->warn('No write action selected.');
            $this->line('Use --dry-run to inspect, --fresh to sync source tables, --reset-ai-output to clear AI output tables, --rebuild-ai to rebuild features, or --score-ai to run scoring.');

            return self::SUCCESS;
        }

        if ($this->option('fresh')) {
            $this->syncSourceTables($selectedTables);
        }

        if ($this->option('reset-ai-output')) {
            $this->resetAiOutputTables();
        }

        if ($this->option('rebuild-ai')) {
            $this->rebuildAiFeatures();
        }

        if ($this->option('score-ai')) {
            $this->scoreAiRecent();
        }

        $this->info('Command completed.');

        return self::SUCCESS;
    }

    private function selectedSourceTables(): array
    {
        $requestedTables = $this->option('table');

        if (empty($requestedTables)) {
            return $this->sourceTables;
        }

        $invalidTables = array_values(array_diff($requestedTables, $this->sourceTables));

        if (!empty($invalidTables)) {
            $this->error('Invalid table(s) requested: ' . implode(', ', $invalidTables));
            $this->line('Allowed source tables: ' . implode(', ', $this->sourceTables));

            return [];
        }

        return $requestedTables;
    }

    private function runDryRun(array $tables): void
    {
        $this->info('Dry-run table report');

        $rows = [];

        // Progress bar is useful here because later this command may inspect many tables.
        $bar = $this->output->createProgressBar(count($tables));
        $bar->start();

        foreach ($tables as $table) {
            $sourceExists = Schema::connection('ai_sync_source')->hasTable($table);
            $targetExists = Schema::connection('ai_sync_target')->hasTable($table);

            if (!$sourceExists || !$targetExists) {
                $rows[] = [
                    $table,
                    $sourceExists ? 'yes' : 'no',
                    $targetExists ? 'yes' : 'no',
                    '-',
                    '-',
                    '-',
                    'missing table',
                ];

                $bar->advance();
                continue;
            }

            $sourceCount = DB::connection('ai_sync_source')->table($table)->count();
            $targetCount = DB::connection('ai_sync_target')->table($table)->count();

            $sourceColumns = Schema::connection('ai_sync_source')->getColumnListing($table);
            $targetColumns = Schema::connection('ai_sync_target')->getColumnListing($table);

            $commonColumns = array_values(array_intersect($sourceColumns, $targetColumns));
            $sourceOnlyColumns = array_values(array_diff($sourceColumns, $targetColumns));
            $targetOnlyColumns = array_values(array_diff($targetColumns, $sourceColumns));

            $notes = [];

            if (!empty($sourceOnlyColumns)) {
                $notes[] = 'source-only: ' . implode(', ', $sourceOnlyColumns);
            }

            if (!empty($targetOnlyColumns)) {
                $notes[] = 'target-only: ' . implode(', ', $targetOnlyColumns);
            }

            if (empty($notes)) {
                $notes[] = 'ok';
            }

            $rows[] = [
                $table,
                'yes',
                'yes',
                number_format($sourceCount),
                number_format($targetCount),
                count($commonColumns),
                implode(' | ', $notes),
            ];

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            [
                'Table',
                'Source?',
                'Target?',
                'Source Rows',
                'Target Rows',
                'Common Columns',
                'Notes',
            ],
            $rows
        );

        $this->info('Dry-run completed. No data was changed.');
    }

    private function syncSourceTables(array $tables): void
    {
        $this->warn('Fresh sync selected. Target source tables will be truncated before copy.');
        $this->newLine();

        $summaryRows = [];

        // Disable FK checks only on target connection while truncating/copying selected tables.
        DB::connection('ai_sync_target')->statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($tables as $table) {
                $this->info("Syncing table: {$table}");

                if (!Schema::connection('ai_sync_source')->hasTable($table)) {
                    $this->warn("  Skipped. Source table does not exist: {$table}");

                    $summaryRows[] = [$table, 'skipped', 'source missing', 0, 0, 0];
                    continue;
                }

                if (!Schema::connection('ai_sync_target')->hasTable($table)) {
                    $this->warn("  Skipped. Target table does not exist: {$table}");

                    $summaryRows[] = [$table, 'skipped', 'target missing', 0, 0, 0];
                    continue;
                }

                $commonColumns = $this->commonColumns($table);

                if (empty($commonColumns)) {
                    $this->warn("  Skipped. No common columns found for {$table}");

                    $summaryRows[] = [$table, 'skipped', 'no common columns', 0, 0, 0];
                    continue;
                }

                DB::connection('ai_sync_target')->table($table)->truncate();

                $sourceCount = DB::connection('ai_sync_source')->table($table)->count();
                $limit = $this->option('limit') ? (int) $this->option('limit') : null;
                $maxRowsToCopy = $limit ?: $sourceCount;

                $copiedRows = $this->copyTableRows($table, $commonColumns, $maxRowsToCopy);
                $targetCount = DB::connection('ai_sync_target')->table($table)->count();

                $summaryRows[] = [
                    $table,
                    'synced',
                    'ok',
                    number_format($sourceCount),
                    number_format($copiedRows),
                    number_format($targetCount),
                ];

                $this->newLine();
            }
        } finally {
            DB::connection('ai_sync_target')->statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->table(
            ['Table', 'Status', 'Notes', 'Source Rows', 'Copied Rows', 'Target Rows'],
            $summaryRows
        );
    }

    private function commonColumns(string $table): array
    {
        $sourceColumns = Schema::connection('ai_sync_source')->getColumnListing($table);
        $targetColumns = Schema::connection('ai_sync_target')->getColumnListing($table);

        // Only copy columns that exist in both DBs.
        // This protects staging/prod sync when source DB does not have AI-added columns yet.
        return array_values(array_intersect($sourceColumns, $targetColumns));
    }

    private function copyTableRows(string $table, array $columns, int $maxRowsToCopy): int
    {
        $copiedRows = 0;
        $chunkSize = 1000;

        $bar = $this->output->createProgressBar($maxRowsToCopy);
        $bar->start();

        $query = DB::connection('ai_sync_source')
            ->table($table)
            ->select($columns);

        // Most PeekTrack tables have id. Use it for stable chunking.
        if (in_array('id', $columns, true)) {
            $query->orderBy('id')->chunkById($chunkSize, function ($rows) use ($table, $columns, $maxRowsToCopy, &$copiedRows, $bar) {
                $remaining = $maxRowsToCopy - $copiedRows;

                if ($remaining <= 0) {
                    return false;
                }

                if ($rows->count() > $remaining) {
                    $rows = $rows->take($remaining);
                }

                $payload = $rows
                    ->map(fn ($row) => (array) $row)
                    ->values()
                    ->all();

                if (!empty($payload)) {
                    DB::connection('ai_sync_target')->table($table)->insert($payload);

                    $inserted = count($payload);
                    $copiedRows += $inserted;
                    $bar->advance($inserted);
                }

                return $copiedRows < $maxRowsToCopy;
            }, 'id');
        } else {
            // Fallback for old/custom tables without id.
            $query->chunk($chunkSize, function ($rows) use ($table, $maxRowsToCopy, &$copiedRows, $bar) {
                $remaining = $maxRowsToCopy - $copiedRows;

                if ($remaining <= 0) {
                    return false;
                }

                if ($rows->count() > $remaining) {
                    $rows = $rows->take($remaining);
                }

                $payload = $rows
                    ->map(fn ($row) => (array) $row)
                    ->values()
                    ->all();

                if (!empty($payload)) {
                    DB::connection('ai_sync_target')->table($table)->insert($payload);

                    $inserted = count($payload);
                    $copiedRows += $inserted;
                    $bar->advance($inserted);
                }

                return $copiedRows < $maxRowsToCopy;
            });
        }

        $bar->finish();
        $this->newLine();

        return $copiedRows;
    }

    private function resetAiOutputTables(): void
    {
        $this->warn('Resetting AI output tables. These will be rebuilt by AI commands.');

        $rows = [];

        DB::connection('ai_sync_target')->statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($this->aiOutputTables as $table) {
                if (!Schema::connection('ai_sync_target')->hasTable($table)) {
                    $rows[] = [$table, 'skipped', 'target table missing'];
                    continue;
                }

                DB::connection('ai_sync_target')->table($table)->truncate();

                $rows[] = [$table, 'reset', 'truncated'];
            }
        } finally {
            DB::connection('ai_sync_target')->statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->table(['Table', 'Status', 'Notes'], $rows);
    }

    /**
     * Rebuild jobcard_ai_features using the existing Sprint 2 backfill command.
     */
    private function rebuildAiFeatures(): void
    {
        $this->info('Rebuilding AI feature rows...');

        $exitCode = $this->call('ai:backfill-features', [
            '--force-rebuild' => true,
        ]);

        if ($exitCode !== self::SUCCESS) {
            $this->error('AI feature rebuild command failed.');
        }
    }

    /**
     * Re-run AI scoring using the existing Sprint 3 score-recent command.
     */
    private function scoreAiRecent(): void
    {
        $this->info('Running AI scoring...');

        $exitCode = $this->call('ai:score-recent', [
            '--since' => '2020-01-01',
            '--limit' => 10000,
            '--rescore' => true,
        ]);

        if ($exitCode !== self::SUCCESS) {
            $this->error('AI scoring command failed.');
        }
    }
}
