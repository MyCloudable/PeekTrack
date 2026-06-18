<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        if (env('IS_DEMO')){
            $schedule->command('migrate:fresh --seed')->everyFifteenMinutes();
        }

        $schedule->command('ai:escalate-kickbacks')
            ->dailyAt('08:00')
            ->withoutOverlapping()
            ->onOneServer()  // if you ever run multiple app servers
            ->appendOutputTo(storage_path('logs/ai-escalate.log'));

        // $schedule->command('ai:capture-rejections')->everyFifteenMinutes();

        $schedule->exec(
            'php -d memory_limit=2048M artisan ai:sync-prod-to-ai-test --fresh --reset-ai-output'
            . ' && php -d memory_limit=2048M artisan ai:backfill-features --since=2000-01-01 --force-rebuild'
            . ' && php -d memory_limit=2048M artisan ai:score-recent --since=2000-01-01 --limit=100000'
            . ' && php -d memory_limit=2048M artisan ai:capture-rejections'
        )
            ->dailyAt('02:30')
            ->withoutOverlapping()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/ai-nightly-pipeline.log'));
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
