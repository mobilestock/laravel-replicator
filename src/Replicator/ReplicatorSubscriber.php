<?php

namespace MobileStock\LaravelReplicator;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use MobileStock\LaravelReplicator\Model\ReplicatorConfig;
use MySQLReplication\Event\DTO\DeleteRowsDTO;
use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\Event\DTO\MariaDbAnnotateRowsDTO;
use MySQLReplication\Event\DTO\RowsDTO;
use MySQLReplication\Event\DTO\UpdateRowsDTO;
use MySQLReplication\Event\DTO\WriteRowsDTO;
use MySQLReplication\Event\EventSubscribers;

class ReplicatorSubscriber extends EventSubscribers
{
    public string $query;

    public function allEvents(EventDTO|MariaDbAnnotateRowsDTO $event): void
    {
        if ($event instanceof MariaDbAnnotateRowsDTO) {
            $this->query = $event->query;
            return;
        }
        if (!($event instanceof WriteRowsDTO || $event instanceof UpdateRowsDTO || $event instanceof DeleteRowsDTO)) {
            return;
        }

        DB::setDefaultConnection('replicator-bridge');

        $database = $event->tableMap->database;
        $table = $event->tableMap->table;

        foreach (Config::get('replicator') as $key => $config) {
            $replicatingTag = '/* isReplicating(' . gethostname() . '_' . $key . ') */';

            if (str_contains($this->query, $replicatingTag)) {
                continue;
            }

            $nodePrimaryDatabase = $config['node_primary']['database'];
            $nodePrimaryTable = $config['node_primary']['table'];
            $nodeSecondaryDatabase = $config['node_secondary']['database'];
            $nodeSecondaryTable = $config['node_secondary']['table'];

            if (
                ($database === $nodePrimaryDatabase && $table === $nodePrimaryTable) ||
                ($database === $nodeSecondaryDatabase && $table === $nodeSecondaryTable)
            ) {
                if (
                    $event->tableMap->database === $nodePrimaryDatabase &&
                    $event->tableMap->table === $nodePrimaryTable
                ) {
                    $nodePrimaryConfig = $config['node_primary'];
                    $nodeSecondaryConfig = $config['node_secondary'];
                    $columnMappings = $config['columns'];
                } else {
                    $nodePrimaryConfig = $config['node_secondary'];
                    $nodeSecondaryConfig = $config['node_primary'];
                    $columnMappings = array_flip($config['columns']);
                }

                $changedColumns = $this->checkChangedColumns($event, $columnMappings);

                if (empty($changedColumns)) {
                    continue;
                }

                $nodePrimaryDatabase = $nodePrimaryConfig['database'];
                $nodePrimaryTable = $nodePrimaryConfig['table'];
                $nodePrimaryReferenceKey = $nodePrimaryConfig['reference_key'];
                $nodeSecondaryDatabase = $nodeSecondaryConfig['database'];
                $nodeSecondaryTable = $nodeSecondaryConfig['table'];
                $nodeSecondaryReferenceKey = $nodeSecondaryConfig['reference_key'];

                foreach ($event->values as $row) {
                    DB::beginTransaction();

                    $rowData = $row;

                    if ($event instanceof WriteRowsDTO) {
                        $columnMappings[$nodePrimaryReferenceKey] = $nodeSecondaryReferenceKey;
                    } elseif ($event instanceof UpdateRowsDTO) {
                        $rowData = $row['after'];
                    }

                    $replicatorInterfaces = File::allFiles(app_path('ReplicatorInterceptors'));

                    foreach ($replicatorInterfaces as $interface) {
                        $className = 'App\\ReplicatorInterceptors\\' . $interface->getFilenameWithoutExtension();
                        $methodName = Str::camel($nodePrimaryTable) . 'X' . Str::camel($nodeSecondaryTable);

                        if (method_exists($className, $methodName)) {
                            $interfaceInstance = new $className();
                            $interfaceInstance->{$methodName}($rowData, $changedColumns);
                            // TODO: ver se é melhor um break ou um continue
                            break;
                        }
                    }

                    $changedColumns[$nodeSecondaryReferenceKey] = $rowData[$nodePrimaryReferenceKey];

                    $databaseHandler = new ReplicateSecondaryNodeHandler(
                        $nodePrimaryReferenceKey,
                        $nodeSecondaryDatabase,
                        $nodeSecondaryTable,
                        $nodeSecondaryReferenceKey,
                        $replicatingTag,
                        $columnMappings,
                        $changedColumns
                    );

                    switch ($event::class) {
                        case UpdateRowsDTO::class:
                            $databaseHandler->update();
                            break;

                        case WriteRowsDTO::class:
                            $databaseHandler->insert();
                            break;

                        case DeleteRowsDTO::class:
                            $databaseHandler->delete();
                            break;
                    }
                    DB::commit();
                }

                $binLogInfo = $event->getEventInfo()->binLogCurrent;
                $replicationModel = new ReplicatorConfig();
                $replicationModel->exists = true;
                $replicationModel->id = 1;
                // @issue https://github.com/mobilestock/backend/issues/639
                $replicationModel->json_binlog = [
                    'file' => $binLogInfo->getBinFileName(),
                    'position' => $binLogInfo->getBinLogPosition(),
                ];
                $replicationModel->save();
            }
        }
    }

    public function checkChangedColumns(RowsDTO $event, array $columnMappings): array
    {
        $changedColumns = [];

        foreach ($event->values as $row) {
            switch ($event::class) {
                case UpdateRowsDTO::class:
                    $before = $row['before'];
                    $after = $row['after'];
                    break;
                case WriteRowsDTO::class:
                case DeleteRowsDTO::class:
                    $before = [];
                    $after = $row;
                    break;
            }
            $before = $row['before'];
            $after = $row['after'];
            foreach ($columnMappings as $nodePrimaryColumn => $nodeSecondaryColumn) {
                if ($before[$nodePrimaryColumn] !== $after[$nodePrimaryColumn]) {
                    $changedColumns[$nodeSecondaryColumn] = $after[$nodePrimaryColumn];
                }
            }
        }

        return $changedColumns;
    }
}
