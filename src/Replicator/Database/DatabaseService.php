<?php

namespace MobileStock\LaravelReplicator\Database;

use Illuminate\Support\Facades\DB;
use MobileStock\LaravelReplicator\Model\ReplicationModel;
use MySQLReplication\Event\Event;

class DatabaseService
{
    protected string $replicateTag;

    public function __construct()
    {
        $this->replicateTag = Event::REPLICATION_QUERY;
        DB::setDefaultConnection('replicator-bridge');
    }

    public function update(string $database, string $table, string $clausule, string $referenceKey, array $binds): void
    {
        $sql = "UPDATE {$database}.{$table}
                        SET {$clausule}
                        WHERE {$database}.{$table}.{$referenceKey} = :{$referenceKey} {$this->replicateTag};";
        DB::update($sql, $binds);
    }

    public function insert(string $database, string $table, string $columns, string $placeholders, array $binds): void
    {
        $sql = "INSERT INTO {$database}.{$table} ({$columns}) VALUES ({$placeholders}) {$this->replicateTag};";

        DB::insert($sql, $binds);
    }

    public function delete(string $database, string $table, string $referenceKey, array $binds): void
    {
        $sql = "DELETE FROM {$database}.{$table} WHERE {$database}.{$table}.{$referenceKey} = :{$referenceKey} {$this->replicateTag};";

        DB::delete($sql, $binds);
    }

    public function getLastBinlogPosition(): ?array
    {
        $replicationModel = new ReplicationModel();
        $results = $replicationModel->query()->first();
        return $results ? json_decode($results->json_binlog, true) : null;
    }

    public function updateBinlogPosition(string $fileName, int $position): void
    {
        $replicationModel = new ReplicationModel();
        $replicationModel->exists = true;
        $replicationModel->id = 1;
        $replicationModel->json_binlog = json_encode(['file' => $fileName, 'position' => $position]);
        $replicationModel->save();
    }
}
