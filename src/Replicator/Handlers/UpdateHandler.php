<?php

namespace MobileStock\LaravelReplicator\Handlers;

use MobileStock\LaravelReplicator\Database\DatabaseService;

class UpdateHandler
{
    public static function handle(
        string $nodePrimaryReferenceKey,
        string $nodeSecondaryDatabase,
        string $nodeSecondaryTable,
        string $nodeSecondaryReferenceKey,
        array $columnMappings,
        array $row
    ): void {
        $before = $row['before'];
        $after = $row['after'];

        $changedColumns = [];
        foreach ($columnMappings as $nodePrimaryColumn => $nodeSecondaryColumn) {
            if ($before[$nodePrimaryColumn] !== $after[$nodePrimaryColumn]) {
                $changedColumns[$nodeSecondaryColumn] = $after[$nodePrimaryColumn];
            }
        }

        if (!empty($changedColumns)) {
            $referenceKeyValue = $after[$nodePrimaryReferenceKey];

            $binds = array_combine(
                array_map(fn($column) => ":{$column}", array_keys($changedColumns)),
                array_values($changedColumns)
            );
            $binds[":{$nodeSecondaryReferenceKey}"] = $referenceKeyValue;

            $clausule = implode(
                ', ',
                array_map(function ($column) use ($nodeSecondaryDatabase, $nodeSecondaryTable) {
                    return "{$nodeSecondaryDatabase}.{$nodeSecondaryTable}.{$column} = :{$column}";
                }, array_keys($changedColumns))
            );

            $databaseHandler = new DatabaseService();
            $databaseHandler->update(
                $nodeSecondaryDatabase,
                $nodeSecondaryTable,
                $clausule,
                $nodeSecondaryReferenceKey,
                $binds
            );
        }
    }
}
