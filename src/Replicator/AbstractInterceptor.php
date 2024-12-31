<?php

namespace MobileStock\LaravelReplicator;

use MySQLReplication\Event\DTO\RowsDTO;

abstract class AbstractInterceptor
{
    public function __construct(protected RowsDTO $event)
    {}
}
