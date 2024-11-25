<?php

namespace MobileStock\LaravelReplicator\Config;

use Illuminate\Support\Facades\Config;

class ReplicationConfigManager
{
    public function getGroupDatabaseConfigurations(): array
    {
        $databases = [];
        $tables = [];

        foreach (Config::get('replicator') as $config) {
            $databases = [$config['node_primary']['database'], $config['node_secondary']['database']];
            $tables = array_merge($tables, [$config['node_primary']['table'], $config['node_secondary']['table']]);
        }

        return [$databases, $tables];
    }
}
