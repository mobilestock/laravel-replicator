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
            File::makeDirectory($directory, 0755, true);
            $this->info("Diretório criado: $directory");
        }

        if (File::exists($filePath)) {
            $this->fail("O arquivo $filePath já existe!");
        }

        $content = "<?php\n\nnamespace App\\ReplicatorInterceptors;\n\nclass $name\n{\n    // Código da classe aqui\n}\n";
        File::put($filePath, $content);

        $this->info("Arquivo criado: $filePath");
    }
}
