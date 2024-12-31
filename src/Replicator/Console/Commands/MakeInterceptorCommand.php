<?php

namespace MobileStock\LaravelReplicator\Console\Commands;

use File;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class MakeInterceptorCommand extends Command
{
    protected $signature = 'replicator:interceptor {name}';
    protected $description = 'Create a new interceptor for laravel replicator';

    public function handle(): void
    {
        $name = $this->argument('name');
        $directory = App::path('ReplicatorInterceptors');
        $filePath = $directory . '/' . $name . '.php';

        if (!File::exists($directory)) {
            File::makeDirectory($directory);
            $this->info("Directory created: $directory");
        }

        if (File::exists($filePath)) {
            $this->fail("The file $filePath already exists");
        }

        $content = <<<PHP
<?php /** @noinspection PhpUnused */

namespace App\\ReplicatorInterceptors;

class $name
{
//
}

PHP;

        File::put($filePath, $content);

        $this->info("Interceptor created: $filePath");
    }
}
