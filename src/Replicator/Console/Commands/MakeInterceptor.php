<?php

namespace MobileStock\LaravelReplicator\Console\Commands;

use Illuminate\Console\Command;

class MakeInterceptor extends Command
{
    protected $signature = 'make:replicator-interceptor {name}';
    protected $description = 'Create a new interceptor for laravel replicator ';

    public function handle(): void
    {
    }
}
