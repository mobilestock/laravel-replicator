<?php

namespace MobileStock\LaravelReplicator\Subscribers;

use MobileStock\LaravelReplicator\Database\DatabaseService;
use MobileStock\LaravelReplicator\Handlers\DeleteHandler;
use MobileStock\LaravelReplicator\Handlers\InsertHandler;
use MobileStock\LaravelReplicator\Handlers\UpdateHandler;
use MobileStock\LaravelReplicator\Helpers\ChangedColumns;
use MobileStock\LaravelReplicator\Interceptor\InterceptorManager;
use MySQLReplication\Event\DTO\DeleteRowsDTO;
use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\Event\DTO\UpdateRowsDTO;
use MySQLReplication\Event\DTO\WriteRowsDTO;
use MySQLReplication\Event\EventSubscribers;

class Registration extends EventSubscribers
{
    private array $configurations;

    public function __construct(array $configurations)
    {
        $this->configurations = $configurations;
    }

    public function allEvents(EventDTO $event): void
    {
        if (!($event instanceof WriteRowsDTO || $event instanceof UpdateRowsDTO || $event instanceof DeleteRowsDTO)) {
            return;
        }

        $database = $event->tableMap->database;
        $table = $event->tableMap->table;

        foreach ($this->configurations as $config) {
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

                $configuredColumns = array_keys($columnMappings);

                $changedColumns = ChangedColumns::getChangedColumns($event);

                if (empty(array_intersect($configuredColumns, $changedColumns))) {
                    continue;
                }

                $nodePrimaryReferenceKey = $nodePrimaryConfig['reference_key'];
                $nodeSecondaryDatabase = $nodeSecondaryConfig['database'];
                $nodeSecondaryTable = $nodeSecondaryConfig['table'];
                $nodeSecondaryReferenceKey = $nodeSecondaryConfig['reference_key'];

                $interceptorFunction = $config['interceptor'] ?? false;

                foreach ($event->values as $row) {
                    switch ($event::class) {
                        case UpdateRowsDTO::class:
                            if ($interceptorFunction) {
                                $row['after'] = InterceptorManager::applyInterceptor(
                                    $interceptorFunction,
                                    $row['after'],
                                    $nodePrimaryTable,
                                    $nodePrimaryDatabase
                                );
                            }

                            UpdateHandler::handle(
                                $nodePrimaryReferenceKey,
                                $nodeSecondaryDatabase,
                                $nodeSecondaryTable,
                                $nodeSecondaryReferenceKey,
                                $columnMappings,
                                $row
                            );
                            break;

                        case WriteRowsDTO::class:
                            if ($interceptorFunction) {
                                $row = InterceptorManager::applyInterceptor(
                                    $interceptorFunction,
                                    $row,
                                    $nodePrimaryTable,
                                    $nodePrimaryDatabase
                                );
                            }

                            InsertHandler::handle($nodeSecondaryDatabase, $nodeSecondaryTable, $columnMappings, $row);
                            break;

                        case DeleteRowsDTO::class:
                            if ($interceptorFunction) {
                                $row = InterceptorManager::applyInterceptor(
                                    $interceptorFunction,
                                    $row,
                                    $nodePrimaryTable,
                                    $nodePrimaryDatabase
                                );
                            }

                            DeleteHandler::handle(
                                $nodeSecondaryDatabase,
                                $nodeSecondaryTable,
                                $nodePrimaryReferenceKey,
                                $nodeSecondaryReferenceKey,
                                $row
                            );
                            break;
                    }
                }

                $binLogInfo = $event->getEventInfo()->binLogCurrent;
                $databaseService = new DatabaseService();
                $databaseService->updateBinlogPosition($binLogInfo->getBinFileName(), $binLogInfo->getBinLogPosition());
            }
        }
    }
}
