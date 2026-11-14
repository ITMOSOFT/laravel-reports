<?php

namespace ItmoSoft\Reports;

use Illuminate\Support\ServiceProvider;

class ReportsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/reports.php', 'itmosoft-reports');

        $this->app->singleton(ReportsManager::class, function ($app) {
            return new ReportsManager($app['db'], $app['config']->get('itmosoft-reports', []));
        });

        $this->app->alias(ReportsManager::class, 'itmosoft.reports');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/reports.php' => config_path('itmosoft-reports.php'),
            ], 'itmosoft-reports-config');
        }
    }
}
