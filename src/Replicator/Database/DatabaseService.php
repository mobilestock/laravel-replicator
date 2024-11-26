<?php

namespace MobileStock\LaravelReplicator\Database;

use Illuminate\Support\Facades\DB;
use MobileStock\LaravelReplicator\Model\ReplicatorConfig;
use MySQLReplication\Event\Event;

class DatabaseService
{
    protected string $replicateTag;

    public function __construct()
    {
        $this->replicateTag = Event::REPLICATION_QUERY;
        DB::setDefaultConnection('replicator-bridge');
    }

    public function getLastBinlogPosition(): ?array
    {
        $replicationModel = new ReplicatorConfig();
        $results = $replicationModel->query()->first();
        return $results ? json_decode($results->json_binlog, true) : null;
    }

    public function updateBinlogPosition(string $fileName, int $position): void
    {
        $replicationModel = new ReplicatorConfig();
        $replicationModel->exists = true;
        $replicationModel->id = 1;
        $replicationModel->json_binlog = json_encode(['file' => $fileName, 'position' => $position]);
        $replicationModel->save();
    }
}
