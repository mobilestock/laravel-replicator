<?php

namespace MobileStock\LaravelReplicator;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use MobileStock\LaravelReplicator\Events\BeforeReplicate;
use MobileStock\LaravelReplicator\Model\ReplicatorConfig;
use MySQLReplication\Event\DTO\DeleteRowsDTO;
use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\Event\DTO\MariaDbAnnotateRowsDTO;
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

                if (!$this->checkChangedColumns($event, array_keys($columnMappings))) {
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

                    $beforeReplicateEvent = new BeforeReplicate(
                        $nodePrimaryDatabase,
                        $nodePrimaryTable,
                        $nodeSecondaryDatabase,
                        $nodeSecondaryTable,
                        $rowData,
                        $event
                    );
                    Event::dispatch($beforeReplicateEvent);

                    if ($event instanceof UpdateRowsDTO) {
                        $beforeReplicateEvent->rowData = [
                            'before' => $row['before'],
                            'after' => $beforeReplicateEvent->rowData,
                        ];
                    }

                    $databaseHandler = new ReplicateSecondaryNodeHandler(
                        $nodePrimaryReferenceKey,
                        $nodeSecondaryDatabase,
                        $nodeSecondaryTable,
                        $nodeSecondaryReferenceKey,
                        $replicatingTag,
                        $columnMappings,
                        $beforeReplicateEvent->rowData
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
                    $changedColumns = array_merge(
                        $changedColumns,
                        array_keys(array_diff_assoc($row['after'], $row['before']))
                    );
                    break;
                case WriteRowsDTO::class:
                    $changedColumns = array_merge($changedColumns, array_keys($row));
                    break;
                case DeleteRowsDTO::class:
                    $changedColumns = array_merge(
                        $changedColumns,
                        array_keys($row['values'] ?? ($row['before'] ?? $row))
                    );
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

        return !empty(array_intersect($configuredColumns, $changedColumns));
    }
}
