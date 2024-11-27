<?php

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\BinLog\BinLogAuthPluginMode;
use MySQLReplication\BinLog\BinLogCurrent;
use MySQLReplication\BinLog\BinLogServerInfo;
use MySQLReplication\BinLog\BinLogSocketConnect;
use MySQLReplication\Config\Config;
use MySQLReplication\Event\Event;
use MySQLReplication\Event\RowEvent\RowEventBuilder;
use MySQLReplication\Event\RowEvent\RowEventFactory;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

test('should make the event with binary data reader', function () {
    $binLogSocketConnectMock = mock(BinLogSocketConnect::class);
    $eventDispatcherMock = mock(EventDispatcherInterface::class);
    $cacheMock = mock(CacheInterface::class);
    $rowEventBuilderMock = mock(RowEventBuilder::class);
    $rowEventFactory = new RowEventFactory($rowEventBuilderMock);

    $config = new Config(
        'test_user',
        '127.0.0.1',
        3306,
        'test_password',
        'utf8',
        '',
        '',
        1,
        'mysql-bin.000001',
        '4',
        [],
        [],
        [],
        [],
        64,
        [],
        0.0,
        'test-uuid'
    );

    $binLogServerInfo = new BinLogServerInfo(
        10,
        '5.7.34-log',
        123456,
        'random_salt',
        BinLogAuthPluginMode::MysqlNativePassword,
        'MySQL',
        5.7
    );

    $binLogCurrent = new BinLogCurrent();
    $binLogCurrent->setBinFileName('binlog.000001');
    $binLogCurrent->setBinLogPosition(12345);

    $rowEventMock = mock(MySQLReplication\Event\RowEvent\RowEvent::class);
    $rowEventBuilderMock->shouldReceive('withBinaryDataReader')->andReturnSelf();
    $rowEventBuilderMock->shouldReceive('withEventInfo')->andReturnSelf();
    $rowEventBuilderMock->shouldReceive('build')->andReturn($rowEventMock);

    $binLogSocketConnectMock->shouldReceive('getCheckSum')->andReturn(1);
    $binLogSocketConnectMock->shouldReceive('getBinLogCurrent')->andReturn($binLogCurrent);

    $binaryData = pack('IICCIICa*', 123456, 10, 5, 0, 0, 100, 123, 'schemaSELECT 1');

    $binaryDataReader = new BinaryDataReader($binaryData);

    $event = new Event(
        $binLogSocketConnectMock,
        $rowEventFactory,
        $eventDispatcherMock,
        $cacheMock,
        $config,
        $binLogServerInfo
    );

    $reflection = new ReflectionClass(Event::class);
    $makeEventMethod = $reflection->getMethod('makeEvent');
    $makeEventMethod->setAccessible(true);

    $result = $makeEventMethod->invoke($event, $binaryDataReader);

    expect($result)->toBeNull();
});
