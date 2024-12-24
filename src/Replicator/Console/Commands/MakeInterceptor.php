<?php

namespace MobileStock\LaravelReplicator\Console\Commands;

use Illuminate\Console\Command;

class StartReplicationCommand extends Command
{
    protected $signature = 'make:replicator-interceptor';
    protected $description = 'Create a new interceptor for laravel replicator ';

    public function handle(): void
    {
    }
}
