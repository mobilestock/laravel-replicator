<?php

namespace MobileStock\LaravelReplicator;

use Illuminate\Support\ServiceProvider;
use MobileStock\LaravelReplicator\Console\Commands\StartReplicationCommand;

class ReplicatorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/replicator-connection.php', 'database.connections.replicator');

        $this->commands(StartReplicationCommand::class);
    }
}
