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

    /**
     * @issue: https://github.com/mobilestock/backend/issues/721
     */
    public function update(): void
    {
        $referenceKeyValue = $this->row[$this->nodeSecondaryReferenceKey];
        unset($this->row[$this->nodeSecondaryReferenceKey]);

        $rowCount = DB::table("{$this->nodeSecondaryDatabase}.{$this->nodeSecondaryTable}")
            ->where($this->nodeSecondaryReferenceKey, $referenceKeyValue)
            ->update($this->row);

        if ($rowCount > 1) {
            throw new DomainException('More than one row tried to update on replicator.');
        }
    }

    /**
     * @issue: https://github.com/mobilestock/backend/issues/721
     */
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

    /**
     * @issue: https://github.com/mobilestock/backend/issues/721
     */
    public function delete(): void
    {
        $referenceKeyValue = $this->row[$this->nodePrimaryReferenceKey];

        $query = DB::table("{$this->nodeSecondaryDatabase}.{$this->nodeSecondaryTable}")->where(
            "{$this->nodeSecondaryReferenceKey}",
            $referenceKeyValue
        );

        $rowCount = $query->delete();

        if ($rowCount > 1) {
            throw new DomainException('More than one row tried to delete on replicator.');
        }
    }
}
