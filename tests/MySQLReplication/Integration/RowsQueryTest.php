<?php

declare(strict_types=1);

use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Event\DTO\QueryDTO;
use Tests\MySQLReplication\Integration\BaseCase;

uses(BaseCase::class);

it('should read the editing query from the binlog', function () {
    $ignoredEvents = [ConstEventType::GTID_LOG_EVENT->value];

    $this->connection->executeStatement(
        'CREATE TABLE test (id INT NOT NULL AUTO_INCREMENT, data VARCHAR (50) NOT NULL, PRIMARY KEY (id))'
    );

    $insertQuery = 'INSERT INTO test (data) VALUES(\'Hello\') /* Foo:Bar; */';
    $this->connection->executeStatement($insertQuery);

    do {
        $event = $this->getEvent();
    } while (in_array($event->getType(), $ignoredEvents, true));
    expect($event)->toBeInstanceOf(QueryDTO::class);

    do {
        $event = $this->getEvent();
    } while (in_array($event->getType(), $ignoredEvents, true));
    expect($event)->toBeInstanceOf(QueryDTO::class);
});
