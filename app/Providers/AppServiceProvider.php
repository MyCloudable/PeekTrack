<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if (PHP_SAPI === 'cli' || $this->app->environment('testing') || $this->app->runningUnitTests()) {
            return;
        }

        $this->app->register(\Reportico\Reportico\ReporticoServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
