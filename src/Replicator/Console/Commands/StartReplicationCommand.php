<?php

namespace MobileStock\LaravelReplicator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use MobileStock\LaravelReplicator\Subscribers\ReplicatorSubscriber;
use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\MySQLReplicationFactory;

class StartReplicationCommand extends Command
{
    protected $signature = 'replicator:start';
    protected $description = 'Starts the replication process configured in the Replicator package';

    public function handle(): void
    {
        DB::setDefaultConnection('replicator-bridge');

        $databases = [];
        $tables = [];

        foreach (Config::get('replicator') as $config) {
            $databases = [$config['node_primary']['database'], $config['node_secondary']['database']];
            $tables = array_merge($tables, [$config['node_primary']['table'], $config['node_secondary']['table']]);
        }

        $builder = (new ConfigBuilder())
            ->withHost(Config::get('database.connections.replicator-bridge.host'))
            ->withPort(Config::get('database.connections.replicator-bridge.port'))
            ->withUser(Config::get('database.connections.replicator-bridge.username'))
            ->withPassword(Config::get('database.connections.replicator-bridge.password'))
            ->withEventsOnly([
                ConstEventType::UPDATE_ROWS_EVENT_V1,
                ConstEventType::WRITE_ROWS_EVENT_V1,
                ConstEventType::DELETE_ROWS_EVENT_V1,
                ConstEventType::MARIA_ANNOTATE_ROWS_EVENT,
            ])
            ->withDatabasesOnly($databases)
            ->withTablesOnly($tables);

        // @issue https://github.com/mobilestock/backend/issues/639
        $lastBinlogPosition = DB::selectOne('SELECT replicator_configs.json_binlog FROM replicator_configs')
            ->json_binlog;

        $lastBinlogPosition = json_decode($lastBinlogPosition, true);

        if (!empty($lastBinlogPosition['file']) && !empty($lastBinlogPosition['position'])) {
            $builder
                ->withBinLogFileName($lastBinlogPosition['file'])
                ->withBinLogPosition($lastBinlogPosition['position']);
        }

        $registrationSubscriber = new ReplicatorSubscriber();
        $replication = new MySQLReplicationFactory($builder->build());
        $replication->registerSubscriber($registrationSubscriber);
        $this->info('Replication process has been started');
        $replication->run();
    }
}
