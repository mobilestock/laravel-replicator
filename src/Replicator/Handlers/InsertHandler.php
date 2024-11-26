<?php

namespace MobileStock\LaravelReplicator\Handlers;

use Illuminate\Support\Facades\DB;
use MySQLReplication\Event\Event;

class InsertHandler
{
    public static function handle(
        string $nodeSecondaryDatabase,
        string $nodeSecondaryTable,
        array $columnMappings,
        array $data
    ): void {
        $mappedData = [];
        foreach ($data as $column => $value) {
            if (isset($columnMappings[$column])) {
                $mappedData[$columnMappings[$column]] = $value;
            } else {
                $mappedData[$column] = $value;
            }
        }

        $binds = array_map(function ($value) {
            return is_null($value) ? null : $value;
        }, $mappedData);

        $columns = implode(',', array_keys($mappedData));
        $placeholders = implode(',', array_map(fn($column) => ":{$column}", array_keys($mappedData)));

        $databaseHandler = new DatabaseService();
        $databaseHandler->insert($nodeSecondaryDatabase, $nodeSecondaryTable, $columns, $placeholders, $binds);
        $sql =
            "INSERT INTO {$nodeSecondaryDatabase}.{$nodeSecondaryTable} ({$columns}) VALUES ({$placeholders})" .
            Event::REPLICATION_QUERY .
            ';';

        DB::insert($sql, $binds);
    }
}
