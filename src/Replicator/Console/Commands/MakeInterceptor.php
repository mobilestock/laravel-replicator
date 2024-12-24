<?php

namespace MobileStock\LaravelReplicator\Console\Commands;

use File;
use Illuminate\Console\Command;

class MakeInterceptor extends Command
{
    protected $signature = 'make:replicator-interceptor {name}';
    protected $description = 'Create a new interceptor for laravel replicator ';

    public function handle(): void
    {
        $name = $this->argument('name');
        $directory = app_path('ReplicatorInterceptors');
        $filePath = $directory . '/' . $name . '.php';

        if (!File::exists($directory)) {
            // TODO: analisar esses parametros e testar a criação
            File::makeDirectory($directory, 0755, true);
            $this->info("Directory created: $directory");
        }

        // TODO: testar essa condicional
        if (File::exists($filePath)) {
            $this->fail("The file $filePath already exists");
        }

        $content = "<?php /** @noinspection PhpUnused */\n\nnamespace App\\ReplicatorInterceptors;\n\nclass $name\n{\n    //\n}\n";
        File::put($filePath, $content);

        $this->info("Interceptor created: $filePath");
    }
}
