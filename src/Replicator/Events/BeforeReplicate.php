<?php

namespace MobileStock\LaravelReplicator\Events;

class BeforeReplicate
{
    public function __construct(
        public string $nodePrimaryDatabase,
        public string $nodePrimaryTable,
        public array $rowData
    ) {
    }
}
