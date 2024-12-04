<?php

namespace MobileStock\LaravelReplicator\Events;

use MySQLReplication\Event\DTO\EventDTO;

class BeforeReplicate
{
    public function __construct(
        public string $nodePrimaryDatabase,
        public string $nodePrimaryTable,
        public array $rowData,
        public EventDTO $replicatorEvent
    ) {
    }
}
