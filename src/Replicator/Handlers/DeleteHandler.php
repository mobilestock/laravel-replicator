<?php

namespace MobileStock\LaravelReplicator\Handlers;

use Illuminate\Support\Facades\DB;
use MySQLReplication\Event\Event;

class DeleteHandler
{
    public static function handle(
        string $nodeSecondaryDatabase,
        string $nodeSecondaryTable,
        string $nodePrimaryReferenceKey,
        string $nodeSecondaryReferenceKey,
        array $data
    ): void {
        $referenceKeyValue = $data[$nodePrimaryReferenceKey];

        $binds = [":{$nodeSecondaryReferenceKey}" => $referenceKeyValue];

        $databaseHandler = new DatabaseService();
        $databaseHandler->delete($nodeSecondaryDatabase, $nodeSecondaryTable, $nodeSecondaryReferenceKey, $binds);
        $sql =
            "DELETE FROM
                {$nodeSecondaryDatabase}.{$nodeSecondaryTable}
            WHERE
                {$nodeSecondaryDatabase}.{$nodeSecondaryTable}.{$nodeSecondaryReferenceKey} = :{$nodeSecondaryReferenceKey}" .
            Event::REPLICATION_QUERY .
            ';';

        DB::delete($sql, $binds);
    }
}
