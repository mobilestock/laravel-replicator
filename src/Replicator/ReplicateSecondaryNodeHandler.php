<?php

namespace MobileStock\LaravelReplicator;

use DomainException;
use Illuminate\Support\Facades\DB;

class ReplicateSecondaryNodeHandler
{
    public function __construct(
        public string $nodePrimaryReferenceKey,
        public string $nodeSecondaryDatabase,
        public string $nodeSecondaryTable,
        public string $nodeSecondaryReferenceKey,
        public string $replicatingTag,
        public array $columnMappings,
        public array $row
    ) {
    }

    public function update(): void
    {
        $referenceKeyValue = $this->row[$this->nodeSecondaryReferenceKey];

        $binds = array_combine(
            array_map(fn($column) => ":{$column}", array_keys($this->row)),
            array_values($this->row)
        );
        $binds[":{$this->nodeSecondaryReferenceKey}"] = $referenceKeyValue;

        $clausule = implode(
            ', ',
            array_map(function ($column) {
                return "{$column} = :{$column}";
            }, array_keys($this->row))
        );

        $sql = "UPDATE {$this->nodeSecondaryDatabase}.{$this->nodeSecondaryTable}
                        SET {$clausule}
                        WHERE
                            {$this->nodeSecondaryDatabase}.{$this->nodeSecondaryTable}.{$this->nodeSecondaryReferenceKey} = :{$this->nodeSecondaryReferenceKey} {$this->replicatingTag};";
        $rowCount = DB::update($sql, $binds);

        if ($rowCount > 1) {
            throw new DomainException("More than one row tried to update on replicator: $sql");
        }
    }

    public function insert(): void
    {
        $columns = implode(',', array_keys($this->row));
        $values = implode(',', array_map(fn($column) => ":{$column}", array_keys($this->row)));

        $sql = "INSERT INTO {$this->nodeSecondaryDatabase}.{$this->nodeSecondaryTable} ({$columns})
                SELECT {$values}
                FROM DUAL
                WHERE NOT EXISTS (
                    SELECT 1
                    FROM {$this->nodeSecondaryDatabase}.{$this->nodeSecondaryTable}
                    WHERE {$this->nodeSecondaryReferenceKey} = :{$this->nodeSecondaryReferenceKey}
                )
                {$this->replicatingTag};
        ";

        DB::insert($sql, $this->row);
    }

    public function delete(): void
    {
        $referenceKeyValue = $this->row[$this->nodePrimaryReferenceKey];

        $binds = [":{$this->nodeSecondaryReferenceKey}" => $referenceKeyValue];

        $sql = "DELETE FROM
                {$this->nodeSecondaryDatabase}.{$this->nodeSecondaryTable}
            WHERE
                {$this->nodeSecondaryDatabase}.{$this->nodeSecondaryTable}.{$this->nodeSecondaryReferenceKey} = :{$this->nodeSecondaryReferenceKey} {$this->replicatingTag};";

        $rowCount = DB::delete($sql, $binds);

        if ($rowCount > 1) {
            throw new DomainException("More than one row tried to delete on replicator: $sql");
        }
    }
}
