<?php

/** @noinspection PhpPossiblePolymorphicInvocationInspection */

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace Tests\MySQLReplication\Integration;

use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Event\DTO\DeleteRowsDTO;
use MySQLReplication\Event\DTO\MariaDbAnnotateRowsDTO;
use MySQLReplication\Event\DTO\QueryDTO;
use MySQLReplication\Event\DTO\RotateDTO;
use MySQLReplication\Event\DTO\TableMapDTO;
use MySQLReplication\Event\DTO\UpdateRowsDTO;
use MySQLReplication\Event\DTO\WriteRowsDTO;
use MySQLReplication\Event\DTO\XidDTO;
use Symfony\Component\EventDispatcher\EventDispatcher;

uses(BaseCase::class);

test('Should get delete event', function () {
    $this->createAndInsertValue(
        'CREATE TABLE test (id INT NOT NULL AUTO_INCREMENT, data VARCHAR (50) NOT NULL, PRIMARY KEY (id))',
        'INSERT INTO test (data) VALUES(\'Hello World\')'
    );

    $this->connection->executeStatement('DELETE FROM test WHERE id = 1');

    expect($this->getEvent())->toBeInstanceOf(XidDTO::class);
    expect($this->getEvent())->toBeInstanceOf(QueryDTO::class);
    expect($this->getEvent())->toBeInstanceOf(MariaDbAnnotateRowsDTO::class);
    expect($this->getEvent())->toBeInstanceOf(TableMapDTO::class);

    /** @var DeleteRowsDTO $event */
    $event = $this->getEvent();
    expect($event)->toBeInstanceOf(DeleteRowsDTO::class);
    expect($event->values[0]['id'])->toEqual(1);
    expect($event->values[0]['data'])->toEqual('Hello World');
});

test('Should get update event', function () {
    $this->createAndInsertValue(
        'CREATE TABLE test (id INT NOT NULL AUTO_INCREMENT, data VARCHAR (50) NOT NULL, PRIMARY KEY (id))',
        'INSERT INTO test (data) VALUES(\'Hello\')'
    );

    $this->connection->executeStatement('UPDATE test SET data = \'World\', id = 2 WHERE id = 1');

    expect($this->getEvent())->toBeInstanceOf(XidDTO::class);
    expect($this->getEvent())->toBeInstanceOf(QueryDTO::class);
    expect($this->getEvent())->toBeInstanceOf(MariaDbAnnotateRowsDTO::class);
    expect($this->getEvent())->toBeInstanceOf(TableMapDTO::class);

    /** @var UpdateRowsDTO $event */
    $event = $this->getEvent();
    expect($event)->toBeInstanceOf(UpdateRowsDTO::class);
    expect($event->values[0]['before']['id'])->toEqual(1);
    expect($event->values[0]['before']['data'])->toEqual('Hello');
    expect($event->values[0]['after']['id'])->toEqual(2);
    expect($event->values[0]['after']['data'])->toEqual('World');
});

test('Should get write event drop table', function () {
    $this->connection->executeStatement($createExpected = 'CREATE TABLE `test` (id INTEGER(11))');
    $this->connection->executeStatement('INSERT INTO `test` VALUES (1)');
    $this->connection->executeStatement($dropExpected = 'DROP TABLE `test`');

    /** @var QueryDTO $event */
    $event = $this->getEvent();
    expect($event)->toBeInstanceOf(QueryDTO::class);
    expect($event->query)->toEqual($createExpected);

    /** @var QueryDTO $event */
    $event = $this->getEvent();
    expect($event)->toBeInstanceOf(QueryDTO::class);
    expect($event->query)->toEqual('BEGIN');

    /** @var MariaDbAnnotateRowsDTO $event */
    expect($this->getEvent())->toBeInstanceOf(MariaDbAnnotateRowsDTO::class);

    /** @var TableMapDTO $event */
    expect($this->getEvent())->toBeInstanceOf(TableMapDTO::class);

    /** @var WriteRowsDTO $event */
    $event = $this->getEvent();
    expect($event)->toBeInstanceOf(WriteRowsDTO::class);
    expect($event->values)->toEqual([]);
    expect($event->changedRows)->toEqual(0);

    expect($this->getEvent())->toBeInstanceOf(XidDTO::class);

    /** @var QueryDTO $event */
    $event = $this->getEvent();
    expect($event)->toBeInstanceOf(QueryDTO::class);
    expect($event->query)->toContain($dropExpected);
});

test('Should get query event create table', function () {
    $this->connection->executeStatement(
        $createExpected =
            'CREATE TABLE test (id INT NOT NULL AUTO_INCREMENT, data VARCHAR (50) NOT NULL, PRIMARY KEY (id))'
    );

    /** @var QueryDTO $event */
    $event = $this->getEvent();
    expect($event)->toBeInstanceOf(QueryDTO::class);
    expect($event->query)->toEqual($createExpected);
});

test('Should drop column', function () {
    $this->disconnect();

    $this->configBuilder->withEventsOnly([
        ['value' => ConstEventType::WRITE_ROWS_EVENT_V1->value],
        ['value' => ConstEventType::WRITE_ROWS_EVENT_V2->value],
    ]);

    $this->connect();

    $this->connection->executeStatement('CREATE TABLE test_drop_column (id INTEGER(11), data VARCHAR(50))');
    $this->connection->executeStatement('INSERT INTO test_drop_column VALUES (1, \'A value\')');
    $this->connection->executeStatement('ALTER TABLE test_drop_column DROP COLUMN data');
    $this->connection->executeStatement('INSERT INTO test_drop_column VALUES (2)');

    /** @var WriteRowsDTO $event */
    $event = $this->getEvent();
    expect($event)->toBeInstanceOf(WriteRowsDTO::class);
    expect($event->values[0])->toEqual([
        'id' => 1,
        'DROPPED_COLUMN_1' => null,
    ]);

    $event = $this->getEvent();
    expect($event)->toBeInstanceOf(WriteRowsDTO::class);
    expect($event->values[0])->toEqual([
        'id' => 2,
    ]);
});

test('Should filter events', function () {
    $this->disconnect();

    $this->configBuilder->withEventsOnly([['value' => ConstEventType::QUERY_EVENT->value]]);

    $this->connect();

    expect($this->getEvent())->toBeInstanceOf(QueryDTO::class);
    expect($this->getEvent())->toBeInstanceOf(QueryDTO::class);

    $this->connection->executeStatement($createTableExpected = 'CREATE TABLE test (id INTEGER(11), data VARCHAR(50))');

    /** @var QueryDTO $event */
    $event = $this->getEvent();
    expect($event)->toBeInstanceOf(QueryDTO::class);
    expect($event->query)->toEqual($createTableExpected);
});

test('Should filter tables', function () {
    $expectedTable = 'test_2';
    $expectedValue = 'foobar';

    $this->disconnect();

    $this->configBuilder
        ->withEventsOnly([
            ['value' => ConstEventType::WRITE_ROWS_EVENT_V1->value],
            ['value' => ConstEventType::WRITE_ROWS_EVENT_V2->value],
        ])
        ->withTablesOnly([$expectedTable]);

    $this->connect();

    $this->connection->executeStatement(
        'CREATE TABLE test_2 (id INT NOT NULL AUTO_INCREMENT, data VARCHAR (50) NOT NULL, PRIMARY KEY (id))'
    );
    $this->connection->executeStatement(
        'CREATE TABLE test_3 (id INT NOT NULL AUTO_INCREMENT, data VARCHAR (50) NOT NULL, PRIMARY KEY (id))'
    );
    $this->connection->executeStatement(
        'CREATE TABLE test_4 (id INT NOT NULL AUTO_INCREMENT, data VARCHAR (50) NOT NULL, PRIMARY KEY (id))'
    );

    $this->connection->executeStatement('INSERT INTO test_4 (data) VALUES (\'foo\')');
    $this->connection->executeStatement('INSERT INTO test_3 (data) VALUES (\'bar\')');
    $this->connection->executeStatement('INSERT INTO test_2 (data) VALUES (\'' . $expectedValue . '\')');

    $event = $this->getEvent();
    expect($event)->toBeInstanceOf(WriteRowsDTO::class);
    expect($event->tableMap->table)->toEqual($expectedTable);
    expect($event->values[0]['data'])->toEqual($expectedValue);
});

test('Should truncate table', function () {
    $this->disconnect();

    $this->configBuilder->withEventsOnly([['value' => ConstEventType::QUERY_EVENT->value]]);

    $this->connect();

    expect($this->getEvent())->toBeInstanceOf(QueryDTO::class);
    expect($this->getEvent())->toBeInstanceOf(QueryDTO::class);

    $this->connection->executeStatement('CREATE TABLE test_truncate_column (id INTEGER(11), data VARCHAR(50))');
    $this->connection->executeStatement('INSERT INTO test_truncate_column VALUES (1, \'A value\')');
    $this->connection->executeStatement('TRUNCATE TABLE test_truncate_column');

    $event = $this->getEvent();
    expect($event->query)->toEqual('CREATE TABLE test_truncate_column (id INTEGER(11), data VARCHAR(50))');
    $event = $this->getEvent();
    expect($event->query)->toEqual('BEGIN');
    $event = $this->getEvent();
    expect($event->query)->toEqual('TRUNCATE TABLE test_truncate_column');
});

test('Should JSON_SET partial update with holes', function () {
    if ($this->checkForVersion(5.7)) {
        $this->markTestIncomplete('Only for mysql 5.7 or higher');
    }

    $expected = '{"age":22,"addr":{"code":100,"detail":{"ab":"970785C8-C299"}},"name":"Alice"}';

    $create_query = 'CREATE TABLE t1 (j JSON)';
    $insert_query = "INSERT INTO t1 VALUES ('" . $expected . "')";

    $this->createAndInsertValue($create_query, $insert_query);

    $this->connection->executeQuery('UPDATE t1 SET j = JSON_SET(j, \'$.addr.detail.ab\', \'970785C8\')');

    expect($this->getEvent())->toBeInstanceOf(XidDTO::class);
    expect($this->getEvent())->toBeInstanceOf(QueryDTO::class);
    expect($this->getEvent())->toBeInstanceOf(MariaDbAnnotateRowsDTO::class);
    expect($this->getEvent())->toBeInstanceOf(TableMapDTO::class);

    /** @var UpdateRowsDTO $event */
    $event = $this->getEvent();

    expect($event)->toBeInstanceOf(UpdateRowsDTO::class);
    expect($event->values[0]['before']['j'])->toEqual($expected);
    expect($event->values[0]['after']['j'])->toEqual(
        '{"age": 22, "addr": {"code": 100, "detail": {"ab": "970785C8"}}, "name": "Alice"}'
    );
});

test('Should JSON_REMOVE partial update with holes', function () {
    if ($this->checkForVersion(5.7)) {
        $this->markTestIncomplete('Only for mysql 5.7 or higher');
    }

    $expected = '{"age":22,"addr":{"code":100,"detail":{"ab":"970785C8-C299"}},"name":"Alice"}';

    $create_query = 'CREATE TABLE t1 (j JSON)';
    $insert_query = "INSERT INTO t1 VALUES ('" . $expected . "')";

    $this->createAndInsertValue($create_query, $insert_query);

    $this->connection->executeStatement('UPDATE t1 SET j = JSON_REMOVE(j, \'$.addr.detail.ab\')');

    expect($this->getEvent())->toBeInstanceOf(XidDTO::class);
    expect($this->getEvent())->toBeInstanceOf(QueryDTO::class);
    expect($this->getEvent())->toBeInstanceOf(MariaDbAnnotateRowsDTO::class);
    expect($this->getEvent())->toBeInstanceOf(TableMapDTO::class);

    /** @var UpdateRowsDTO $event */
    $event = $this->getEvent();

    expect($event)->toBeInstanceOf(UpdateRowsDTO::class);
    expect($event->values[0]['before']['j'])->toEqual($expected);
    expect($event->values[0]['after']['j'])->toEqual(
        '{"age": 22, "addr": {"code": 100, "detail": {}}, "name": "Alice"}'
    );
});

test('Should JSON_REPLACE partial update with holes', function () {
    if ($this->checkForVersion(5.7)) {
        $this->markTestIncomplete('Only for mysql 5.7 or higher');
    }

    $expected = '{"age":22,"addr":{"code":100,"detail":{"ab":"970785C8-C299"}},"name":"Alice"}';

    $create_query = 'CREATE TABLE t1 (j JSON)';
    $insert_query = "INSERT INTO t1 VALUES ('" . $expected . "')";

    $this->createAndInsertValue($create_query, $insert_query);

    $this->connection->executeStatement('UPDATE t1 SET j = JSON_REPLACE(j, \'$.addr.detail.ab\', \'9707\')');

    expect($this->getEvent())->toBeInstanceOf(XidDTO::class);
    expect($this->getEvent())->toBeInstanceOf(QueryDTO::class);
    expect($this->getEvent())->toBeInstanceOf(MariaDbAnnotateRowsDTO::class);
    expect($this->getEvent())->toBeInstanceOf(TableMapDTO::class);

    /** @var UpdateRowsDTO $event */
    $event = $this->getEvent();

    expect($event)->toBeInstanceOf(UpdateRowsDTO::class);
    expect($event->values[0]['before']['j'])->toEqual($expected);
    expect($event->values[0]['after']['j'])->toEqual(
        '{"age": 22, "addr": {"code": 100, "detail": {"ab": "9707"}}, "name": "Alice"}'
    );
});

test('Should rotate log', function () {
    $this->connection->executeStatement('FLUSH LOGS');

    expect($this->getEvent())->toBeInstanceOf(RotateDTO::class);

    expect($this->getEvent()->getEventInfo()->binLogCurrent->getBinFileName())->toMatch('/^[a-z-]+\.[\d]+$/');
});

test('Should use provided event dispatcher', function () {
    $this->disconnect();

    $testEventSubscribers = new TestEventSubscribers($this);

    $eventDispatcher = new EventDispatcher();
    $eventDispatcher->addSubscriber($testEventSubscribers);

    $this->connectWithProvidedEventDispatcher($eventDispatcher);

    $createExpected =
        'CREATE TABLE test (id INT NOT NULL AUTO_INCREMENT, data VARCHAR (50) NOT NULL, PRIMARY KEY (id))';
    $this->connection->executeStatement($createExpected);

    /** @var QueryDTO $event */
    $event = $this->getEvent();
    $this->assertInstanceOf(QueryDTO::class, $event);
    $this->assertEquals($createExpected, $event->query);
});
