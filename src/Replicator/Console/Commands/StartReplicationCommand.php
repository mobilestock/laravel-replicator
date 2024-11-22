<?php

namespace MobileStock\LaravelReplicator\Console\Commands;

use Config;
use Illuminate\Console\Command;
use MobileStock\LaravelReplicator\Config\ReplicationConfigManager;
use MobileStock\LaravelReplicator\Database\DatabaseService;
use MobileStock\LaravelReplicator\Subscribers\Registration;
use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\MySQLReplicationFactory;

class StartReplicationCommand extends Command
{
    protected $signature = 'replicator:start';
    protected $description = 'Starts the replication process configured in the Replicator package';

    public function handle(): void
    {
        $configManager = new ReplicationConfigManager();
        $configurations = $configManager->getConfigurations();

        $databases = $configManager->getDatabases();
        $tables = $configManager->getTables();

        $builder = (new ConfigBuilder())
            ->withHost(Config::get('database.connections.replicator.host'))
            ->withPort(Config::get('database.connections.replicator.port'))
            ->withUser(Config::get('database.connections.replicator.username'))
            ->withPassword(Config::get('database.connections.replicator.password'))
            ->withEventsOnly([
                ConstEventType::UPDATE_ROWS_EVENT_V1,
                ConstEventType::WRITE_ROWS_EVENT_V1,
                ConstEventType::DELETE_ROWS_EVENT_V1,
                ConstEventType::MARIA_ANNOTATE_ROWS_EVENT,
            ])
            ->withDatabasesOnly($databases)
            ->withTablesOnly($tables);

        $databaseService = new DatabaseService();
        $lastBinlogPosition = $databaseService->getLastBinlogPosition();

        if (!empty($lastBinlogPosition['file']) && !empty($lastBinlogPosition['position'])) {
            $builder
                ->withBinLogFileName($lastBinlogPosition['file'])
                ->withBinLogPosition($lastBinlogPosition['position']);
        }

        $registrationSubscriber = new Registration($configurations);
        $replication = new MySQLReplicationFactory($builder->build());
        $replication->registerSubscriber($registrationSubscriber);

        $replication->run();
    }
}
