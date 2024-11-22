<?php

namespace MobileStock\LaravelReplicator\Database;

use Illuminate\Support\Facades\DB;
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
        DB::setDefaultConnection('replicator');
        $lastBinlogChange = DB::selectOne('SELECT settings.json_binlog FROM settings');
        return json_decode($lastBinlogChange->json_binlog, true);
    }

    public function updateBinlogPosition(string $fileName, int $position): void
    {
        DB::setDefaultConnection('replicator');
        $jsonBinlog = json_encode(['file' => $fileName, 'position' => $position]);
        DB::update('UPDATE settings SET settings.json_binlog = :json_binlog WHERE true;', [
            'json_binlog' => $jsonBinlog,
        ]);
    }
}
