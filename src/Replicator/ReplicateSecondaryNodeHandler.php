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
        public array  $columnMappings,
        public array  $row
    )
    {
    }

    public function update(): void
    {
        $before = $this->row['before'];
        $after = $this->row['after'];

        $changedColumns = [];
        foreach ($this->columnMappings as $nodePrimaryColumn => $nodeSecondaryColumn) {
            if ($before[$nodePrimaryColumn] !== $after[$nodePrimaryColumn]) {
                $changedColumns[$nodeSecondaryColumn] = $after[$nodePrimaryColumn];
            }
        }

        $referenceKeyValue = $after[$this->nodePrimaryReferenceKey];

        $binds = array_combine(
            array_map(fn($column) => ":{$column}", array_keys($changedColumns)),
            array_values($changedColumns)
        );
        $binds[":{$this->nodeSecondaryReferenceKey}"] = $referenceKeyValue;

        $clausule = implode(
            ', ',
            array_map(function ($column) {
                return "{$column} = :{$column}";
            }, array_keys($changedColumns))
        );

        $sql =
            "UPDATE {$this->nodeSecondaryDatabase}.{$this->nodeSecondaryTable}
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
        $mappedData = [];
        foreach ($this->row as $column => $value) {
            if (!isset($this->columnMappings[$column])) {
                continue;
            }
            $mappedData[$this->columnMappings[$column]] = $value;
        }

        $columns = implode(',', array_keys($mappedData));
        $placeholders = implode(',', array_map(fn($column) => ":{$column}", array_keys($mappedData)));

        $sql =
            "INSERT INTO {$this->nodeSecondaryDatabase}.{$this->nodeSecondaryTable} ({$columns}) VALUES ({$placeholders}) {$this->replicatingTag};";

        DB::insert($sql, $mappedData);
    }

    public function delete(): void
    {
        $referenceKeyValue = $this->row[$this->nodePrimaryReferenceKey];

        $binds = [":{$this->nodeSecondaryReferenceKey}" => $referenceKeyValue];

        $sql =
            "DELETE FROM
                {$this->nodeSecondaryDatabase}.{$this->nodeSecondaryTable}
            WHERE
                {$this->nodeSecondaryDatabase}.{$this->nodeSecondaryTable}.{$this->nodeSecondaryReferenceKey} = :{$this->nodeSecondaryReferenceKey} {$this->replicatingTag} ;";

        $rowCount = DB::delete($sql, $binds);

        if ($rowCount > 1) {
            throw new DomainException("More than one row tried to delete on replicator: $sql");
        }
    }
}
