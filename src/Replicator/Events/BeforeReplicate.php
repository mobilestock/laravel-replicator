<?php

namespace MobileStock\LaravelReplicator\Events;

use MySQLReplication\Event\DTO\EventDTO;

class BeforeReplicate
{
    public function __construct(
        public string $nodePrimaryDatabase,
        public string $nodePrimaryTable,
        public string $nodeSecondaryDatabase,
        public string $nodeSecondaryTable,
        public array $rowData,
        public EventDTO $replicatorEvent
    ) {
    }
}
