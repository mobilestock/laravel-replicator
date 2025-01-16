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
        $caminho = storage_path('logs/replicator.txt');
        file_put_contents($caminho, '1', FILE_APPEND);
        if ($event instanceof MariaDbAnnotateRowsDTO) {
            file_put_contents($caminho, "2 {$event->query}", FILE_APPEND);
            $this->query = $event->query;
            return;
        }
        file_put_contents($caminho, '3', FILE_APPEND);
        if (!$event instanceof RowsDTO) {
            return;
        }
        file_put_contents($caminho, '4', FILE_APPEND);

        DB::setDefaultConnection('replicator-bridge');

        $database = $event->tableMap->database;
        $table = $event->tableMap->table;

        file_put_contents($caminho, "5 $database $table", FILE_APPEND);

        foreach (Config::get('replicator') as $key => $config) {
            $replicatingTag = '/* isReplicating(' . gethostname() . '_' . $key . ') */';

            file_put_contents($caminho, '6', FILE_APPEND);
            if (str_contains($this->query, $replicatingTag)) {
                continue;
            }
            file_put_contents($caminho, '7', FILE_APPEND);

            $nodePrimaryDatabase = $config['node_primary']['database'];
            $nodePrimaryTable = $config['node_primary']['table'];
            $nodeSecondaryDatabase = $config['node_secondary']['database'];
            $nodeSecondaryTable = $config['node_secondary']['table'];

            if (
                ($database === $nodePrimaryDatabase && $table === $nodePrimaryTable) ||
                ($database === $nodeSecondaryDatabase && $table === $nodeSecondaryTable)
            ) {
                file_put_contents($caminho, '8', FILE_APPEND);
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
                file_put_contents($caminho, '9', FILE_APPEND);

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
                    file_put_contents($caminho, '10', FILE_APPEND);

                    $rowData = $row;

                    if ($event instanceof WriteRowsDTO) {
                        $columnMappings[$nodePrimaryReferenceKey] = $nodeSecondaryReferenceKey;
                    } elseif ($event instanceof UpdateRowsDTO) {
                        $rowData = $row['after'];
                    }
                    file_put_contents($caminho, '11', FILE_APPEND);

                    $interceptorsDirectory = App::path('ReplicatorInterceptors');
                    file_put_contents($caminho, '12', FILE_APPEND);
                    if (!($event instanceof DeleteRowsDTO) && File::isDirectory($interceptorsDirectory)) {
                        file_put_contents($caminho, '13', FILE_APPEND);
                        $replicatorInterfaces = File::allFiles($interceptorsDirectory);

                        foreach ($replicatorInterfaces as $interface) {
                            $file = App::path('ReplicatorInterceptors/' . $interface->getFilename());
                            $fileContent = file_get_contents($file);
                            file_put_contents($caminho, '14', FILE_APPEND);

                            if (!preg_match('/^namespace\s+(.+?);$/sm', $fileContent, $matches)) {
                                throw new LogicException('Namespace not found in ' . $file);
                            }
                            $namespace = $matches[1];

                            file_put_contents($caminho, '15', FILE_APPEND);

                            $className = $namespace . '\\' . $interface->getFilenameWithoutExtension();
                            $methodName = Str::camel($nodePrimaryTable) . 'X' . Str::camel($nodeSecondaryTable);
                            file_put_contents($caminho, '16', FILE_APPEND);

                            if (method_exists($className, $methodName)) {
                                file_put_contents($caminho, '17', FILE_APPEND);
                                $interfaceInstance = App::make($className, ['event' => $event]);
                                /**
                                 * @issue https://github.com/mobilestock/backend/issues/731
                                 */
                                $changedColumns = $interfaceInstance->{$methodName}($rowData, $changedColumns);
                                break;
                            }
                        }
                    }

                    file_put_contents($caminho, '18', FILE_APPEND);
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
                    file_put_contents($caminho, '19', FILE_APPEND);

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
                    file_put_contents($caminho, '20', FILE_APPEND);
                    DB::commit();
                }

                file_put_contents($caminho, '21', FILE_APPEND);
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
