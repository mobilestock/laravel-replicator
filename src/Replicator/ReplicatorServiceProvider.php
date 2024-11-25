<?php

namespace MobileStock\LaravelReplicator;

use Illuminate\Support\ServiceProvider;
use MobileStock\LaravelReplicator\Console\Commands\StartReplicationCommand;

class ReplicatorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes(
            [
                __DIR__ . '/../../migrations/' => database_path('migrations'),
            ],
            'replicator-migrations'
        );
    }

    public function register(): void
    {
        $this->commands(StartReplicationCommand::class);
    }
}
