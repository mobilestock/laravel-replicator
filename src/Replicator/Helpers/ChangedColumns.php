<?php

namespace MobileStock\LaravelReplicator\Helpers;

use MySQLReplication\Event\DTO\DeleteRowsDTO;
use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\Event\DTO\UpdateRowsDTO;
use MySQLReplication\Event\DTO\WriteRowsDTO;

class ChangedColumns
{
    public static function checkChangedColumns(EventDTO $event, array $configuredColumns): bool
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
        }

        if (empty(array_intersect($configuredColumns, $changedColumns))) {
            return false;
        }

        return true;
    }
}
