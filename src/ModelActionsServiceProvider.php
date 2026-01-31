<?php

namespace HosnyAdeeb\ModelActions;

use HosnyAdeeb\ModelActions\Console\Commands\MakeActionCommand;
use HosnyAdeeb\ModelActions\Console\Commands\MakeActionsCommand;
use Illuminate\Support\ServiceProvider;

class ModelActionsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge package config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/model-actions.php',
            'model-actions'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register the artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeActionsCommand::class,
                MakeActionCommand::class,
            ]);

            // Publish config file
            $this->publishes([
                __DIR__ . '/../config/model-actions.php' => config_path('model-actions.php'),
            ], 'model-actions-config');

            // Publish stubs for customization
            $this->publishes([
                __DIR__ . '/../stubs' => base_path('stubs/model-actions'),
            ], 'model-actions-stubs');
        }
    }
}
