<?php

namespace MobileStock\LaravelReplicator;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use LogicException;
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

    public function allEvents(EventDTO $event): void
    {
        echo '1';
        if ($event instanceof MariaDbAnnotateRowsDTO) {
            echo "2 {$event->query}";
            $this->query = $event->query;
            return;
        }
        echo '3';
        if (!$event instanceof RowsDTO) {
            return;
        }
        echo '4';

        DB::setDefaultConnection('replicator-bridge');

        $database = $event->tableMap->database;
        $table = $event->tableMap->table;

        echo '5';

        foreach (Config::get('replicator') as $key => $config) {
            $replicatingTag = '/* isReplicating(' . gethostname() . '_' . $key . ') */';

            echo '6';
            if (str_contains($this->query, $replicatingTag)) {
                continue;
            }
            echo '7';

            $nodePrimaryDatabase = $config['node_primary']['database'];
            $nodePrimaryTable = $config['node_primary']['table'];
            $nodeSecondaryDatabase = $config['node_secondary']['database'];
            $nodeSecondaryTable = $config['node_secondary']['table'];

            if (
                ($database === $nodePrimaryDatabase && $table === $nodePrimaryTable) ||
                ($database === $nodeSecondaryDatabase && $table === $nodeSecondaryTable)
            ) {
                echo '8';
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

                $changedColumns = $this->getChangedColuns($event, $columnMappings);
                echo '9';

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
                    echo '10';

                    $rowData = $row;

                    if ($event instanceof WriteRowsDTO) {
                        $columnMappings[$nodePrimaryReferenceKey] = $nodeSecondaryReferenceKey;
                    } elseif ($event instanceof UpdateRowsDTO) {
                        $rowData = $row['after'];
                    }
                    echo '11';

                    $interceptorsDirectory = App::path('ReplicatorInterceptors');
                    echo '12';
                    if (!($event instanceof DeleteRowsDTO) && File::isDirectory($interceptorsDirectory)) {
                        echo '13';
                        $replicatorInterfaces = File::allFiles($interceptorsDirectory);

                        foreach ($replicatorInterfaces as $interface) {
                            $file = App::path('ReplicatorInterceptors/' . $interface->getFilename());
                            $fileContent = file_get_contents($file);
                            echo '14';

                            if (!preg_match('/^namespace\s+(.+?);$/sm', $fileContent, $matches)) {
                                throw new LogicException('Namespace not found in ' . $file);
                            }
                            $namespace = $matches[1];

                            echo '15';

                            $className = $namespace . '\\' . $interface->getFilenameWithoutExtension();
                            $methodName = Str::camel($nodePrimaryTable) . 'X' . Str::camel($nodeSecondaryTable);
                            echo '16';

                            if (method_exists($className, $methodName)) {
                                echo '17';
                                $interfaceInstance = App::make($className, ['event' => $event]);
                                /**
                                 * @issue https://github.com/mobilestock/backend/issues/731
                                 */
                                $changedColumns = $interfaceInstance->{$methodName}($rowData, $changedColumns);
                                break;
                            }
                        }
                    }

                    echo '18';
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
                    echo '19';

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
                    echo '20';
                    DB::commit();
                }

                echo '21';
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

    public function getChangedColuns(RowsDTO $event, array $columnMappings): array
    {
        $changedColumns = [];

        foreach ($event->values as $row) {
            switch ($event::class) {
                case UpdateRowsDTO::class:
                    $before = $row['before'];
                    $after = $row['after'];
                    foreach ($columnMappings as $nodePrimaryColumn => $nodeSecondaryColumn) {
                        if ($before[$nodePrimaryColumn] !== $after[$nodePrimaryColumn]) {
                            $changedColumns[$nodeSecondaryColumn] = $after[$nodePrimaryColumn];
                        }
                    }
                    break;
                case WriteRowsDTO::class:
                    foreach ($columnMappings as $nodePrimaryColumn => $nodeSecondaryColumn) {
                        $changedColumns[$nodeSecondaryColumn] = $row[$nodePrimaryColumn];
                    }
                    break;
                case DeleteRowsDTO::class:
                    $changedColumns = $row;
                    break;
            }
        }

        return $changedColumns;
    }
}
