<?php

namespace MobileStock\LaravelReplicator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use MobileStock\LaravelReplicator\Model\ReplicatorConfig;
use MobileStock\LaravelReplicator\ReplicatorSubscriber;
use MySQLReplication\BinLog\BinLogException;
use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\MySQLReplicationFactory;

class StartReplicationCommand extends Command
{
    protected $signature = 'replicator:start';
    protected $description = 'Starts the replication process configured in the Replicator package';

    public function handle(): void
    {
        try {
            DB::setDefaultConnection('replicator-bridge');

            $databases = [];
            $tables = [];

            foreach (Config::get('replicator') as $config) {
                array_push($databases, $config['node_primary']['database'], $config['node_secondary']['database']);
                array_push($tables, $config['node_primary']['table'], $config['node_secondary']['table']);
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
                ->withDatabasesOnly(array_unique($databases))
                ->withTablesOnly(array_unique($tables))
                ->withSlaveId(rand());

            // @issue https://github.com/mobilestock/backend/issues/639
            $lastBinlogPosition = DB::selectOne('SELECT replicator_configs.json_binlog FROM replicator_configs')[
                'binlog'
            ];

            if (!empty($lastBinlogPosition['file']) && !empty($lastBinlogPosition['position'])) {
                $builder
                    ->withBinLogFileName($lastBinlogPosition['file'])
                    ->withBinLogPosition($lastBinlogPosition['position']);
            }

            $registrationSubscriber = new ReplicatorSubscriber();
            $replication = new MySQLReplicationFactory($builder->build());
            $replication->registerSubscriber($registrationSubscriber);
            $this->info('Replication process has been started');
            $this->info(
                'Replicator bridge:' .
                    PHP_EOL .
                    json_encode(Config::get('database.connections.replicator-bridge')) .
                    PHP_EOL
            );
            $this->info('Array unique databases:' . PHP_EOL . array_unique($databases) . PHP_EOL);
            $this->info('Array unique tables:' . PHP_EOL . array_unique($tables) . PHP_EOL);
            $replication->run();
        } catch (BinLogException $exception) {
            if ($exception->getCode() === 1236 && !App::isProduction()) {
                $binlogStatus = DB::selectOne('SHOW MASTER STATUS');
                $file = $binlogStatus['File'];
                $position = $binlogStatus['Position'];
                $replicationModel = new ReplicatorConfig();
                $replicationModel->exists = true;
                // @issue https://github.com/mobilestock/backend/issues/674
                $replicationModel->id = 1;
                // @issue https://github.com/mobilestock/backend/issues/639
                $replicationModel->json_binlog = [
                    'file' => $file,
                    'position' => $position,
                ];
                $replicationModel->update();
                return;
            }

            throw $exception;
        }
    }
}
