<?php

namespace MobileStock\LaravelReplicator\Database;

use Illuminate\Support\Facades\DB;
use MySQLReplication\Event\Event;

class DatabaseService
{
    protected string $replicateTag;
    protected string $replicatorDb;

    public function __construct()
    {
        $this->replicateTag = Event::REPLICATION_QUERY;
        $this->replicatorDb = env('REPLICATOR_DB');
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
        $lastBinlogChange = DB::selectOne("SELECT json_binlog FROM {$this->replicatorDb}.settings");
        if ($lastBinlogChange) {
            return json_decode($lastBinlogChange->json_binlog, true);
        }
        return null;
    }

    public function updateBinlogPosition(string $fileName, int $position): void
    {
        $jsonBinlog = json_encode(['file' => $fileName, 'position' => $position]);
        DB::update("UPDATE {$this->replicatorDb}.settings SET json_binlog = :json_binlog WHERE true;", [
            'json_binlog' => $jsonBinlog,
        ]);
    }
}
