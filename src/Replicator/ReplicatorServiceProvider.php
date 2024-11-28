<?php

namespace MobileStock\LaravelReplicator;

use Illuminate\Support\ServiceProvider;
use MobileStock\LaravelReplicator\Console\Commands\StartReplicationCommand;

class ReplicatorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations/');

        $this->publishes([
            __DIR__ . '/../../config/replicator.php' => config_path('replicator.php'),
        ]);
    }

    public function register(): void
    {
        $this->commands(StartReplicationCommand::class);
    }
}
