<?php

namespace MobileStock\LaravelReplicator\Database;

use DomainException;
use Illuminate\Support\Facades\DB;
use MySQLReplication\Event\Event;

class DatabaseHandler
{
    public string $nodePrimaryReferenceKey;
    public string $nodeSecondaryDatabase;
    public string $nodeSecondaryTable;
    public string $nodeSecondaryReferenceKey;
    public array $columnMappings;
    public array $row;

    public function __construct(
        string $nodePrimaryReferenceKey,
        string $nodeSecondaryDatabase,
        string $nodeSecondaryTable,
        string $nodeSecondaryReferenceKey,
        array $columnMappings,
        array $row
    ) {
        $this->nodePrimaryReferenceKey = $nodePrimaryReferenceKey;
        $this->nodeSecondaryDatabase = $nodeSecondaryDatabase;
        $this->nodeSecondaryTable = $nodeSecondaryTable;
        $this->nodeSecondaryReferenceKey = $nodeSecondaryReferenceKey;
        $this->columnMappings = $columnMappings;
        $this->row = $row;
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
        echo 'Changed Columns:';
        dump($changedColumns);

        $referenceKeyValue = $after[$this->nodePrimaryReferenceKey];

        $binds = array_combine(
            array_map(fn($column) => ":{$column}", array_keys($changedColumns)),
            array_values($changedColumns)
        );
        $binds[":{$this->nodeSecondaryReferenceKey}"] = $referenceKeyValue;

        echo 'Binds:';
        dump($binds);
        $clausule = implode(
            ', ',
            array_map(function ($column) {
                return "{$column} = :{$column}";
            }, array_keys($changedColumns))
        );
        echo 'Clausule:', $clausule;

        echo 'Before:';
        dump($before);
        echo 'After:';
        dump($after);

        echo 'Column Mappings:';
        dump($this->columnMappings);

        $sql =
            "UPDATE {$this->nodeSecondaryDatabase}.{$this->nodeSecondaryTable}
                        SET {$clausule}
                        WHERE {$this->nodeSecondaryDatabase}.{$this->nodeSecondaryTable}.{$this->nodeSecondaryReferenceKey} = :{$this->nodeSecondaryReferenceKey}" .
            Event::REPLICATION_QUERY .
            ';';
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

        echo 'Mapped Data:';
        dump($mappedData);

        $columns = implode(',', array_keys($mappedData));
        $placeholders = implode(',', array_map(fn($column) => ":{$column}", array_keys($mappedData)));

        echo 'Columns:', $columns;
        echo 'Placeholders:', $placeholders;

        $sql =
            "INSERT INTO {$this->nodeSecondaryDatabase}.{$this->nodeSecondaryTable} ({$columns}) VALUES ({$placeholders})" .
            Event::REPLICATION_QUERY .
            ';';

        DB::insert($sql, $mappedData);
    }

    public function delete(): void
    {
        $referenceKeyValue = $this->row[$this->nodePrimaryReferenceKey];

        $binds = [":{$this->nodeSecondaryReferenceKey}" => $referenceKeyValue];

        echo 'Binds:';
        dump($binds);

        $sql =
            "DELETE FROM
                {$this->nodeSecondaryDatabase}.{$this->nodeSecondaryTable}
            WHERE
                {$this->nodeSecondaryDatabase}.{$this->nodeSecondaryTable}.{$this->nodeSecondaryReferenceKey} = :{$this->nodeSecondaryReferenceKey}" .
            Event::REPLICATION_QUERY .
            ';';

        $rowCount = DB::delete($sql, $binds);

        if ($rowCount > 1) {
            throw new DomainException("More than one row tried to delete on replicator: $sql");
        }
    }
}
