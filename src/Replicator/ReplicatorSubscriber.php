<?php

namespace MobileStock\LaravelReplicator;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
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
        if ($event instanceof MariaDbAnnotateRowsDTO) {
            $this->query = $event->query;
            return;
        }
        if (!$event instanceof RowsDTO) {
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

                $changedColumns = $this->getChangedColuns($event, $columnMappings);

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

                    $interceptorsDirectory = App::path('ReplicatorInterceptors');
                    if (!($event instanceof DeleteRowsDTO) && File::isDirectory($interceptorsDirectory)) {
                        $replicatorInterfaces = File::allFiles($interceptorsDirectory);

                        foreach ($replicatorInterfaces as $interface) {
                            $className = 'ReplicatorInterceptors\\' . $interface->getFilenameWithoutExtension();
                            $className = $this->findNamespaceFromClass($className);
                            $methodName = Str::camel($nodePrimaryTable) . 'X' . Str::camel($nodeSecondaryTable);

                            if (method_exists($className, $methodName)) {
                                $interfaceInstance = App::make($className, ['event' => $event]);
                                $changedColumns = $interfaceInstance->{$methodName}($rowData, $changedColumns);
                                break;
                            }
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

    public function findNamespaceFromClass(string $className): ?string
    {
        $autoloadPath = App::basePath('vendor/autoload.php');

        $composerAutoload = require $autoloadPath;

        $namespaces = $composerAutoload->getPrefixesPsr4();

        foreach ($namespaces as $namespace => $paths) {
            foreach ($paths as $path) {
                $classPath = $path . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
                if (file_exists($classPath)) {
                    return $namespace . $className;
                }
            }
        }

        return null;
    }
}
