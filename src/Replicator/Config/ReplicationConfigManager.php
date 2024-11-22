<?php

namespace MobileStock\LaravelReplicator\Config;

use Illuminate\Support\Facades\Config;

class ReplicationConfigManager
{
    private array $configurations;
    private array $databases;
    private array $tables;

    public function __construct()
    {
        $this->loadConfigurations();
    }

    private function loadConfigurations(): void
    {
        $this->configurations = Config::get('replicator');

        $databases = [];
        $tables = [];

        foreach ($this->configurations as $config) {
            $databases = [$config['node_primary']['database'], $config['node_secondary']['database']];
            $tables = array_merge($tables, [$config['node_primary']['table'], $config['node_secondary']['table']]);
        }

        $this->databases = array_unique($databases);
        $this->tables = array_unique($tables);
    }

    public function getConfigurations(): array
    {
        return $this->configurations;
    }

    public function getDatabases(): array
    {
        return $this->databases;
    }

    public function getTables(): array
    {
        return $this->tables;
    }
}
