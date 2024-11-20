<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

use MySQLReplication\Config\Config;
use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Config\ConfigException;
use PHPUnit\Framework\Attributes\DataProvider;

function shouldMakeConfig(): void
{
    $expected = [
        'user' => 'foo',
        'host' => '127.0.0.1',
        'port' => 3308,
        'password' => 'secret',
        'charset' => 'utf8',
        'gtid' => '9b1c8d18-2a76-11e5-a26b-000c2976f3f3:1-177592',
        'slaveId' => 1,
        'binLogFileName' => 'binfile1.bin',
        'binLogPosition' => '999',
        'eventsOnly' => [],
        'eventsIgnore' => [],
        'tablesOnly' => ['test_table'],
        'databasesOnly' => ['test_database'],
        'mariaDbGtid' => '123:123',
        'tableCacheSize' => 777,
        'custom' => [
            [
                'random' => 'data',
            ],
        ],
        'heartbeatPeriod' => 69,
        'slaveUuid' => '6c27ed6d-7ee1-11e3-be39-6c626d957cff',
    ];

    $config = new Config(
        $expected['user'],
        $expected['host'],
        $expected['port'],
        $expected['password'],
        $expected['charset'],
        $expected['gtid'],
        $expected['mariaDbGtid'],
        $expected['slaveId'],
        $expected['binLogFileName'],
        $expected['binLogPosition'],
        $expected['eventsOnly'],
        $expected['eventsIgnore'],
        $expected['tablesOnly'],
        $expected['databasesOnly'],
        $expected['tableCacheSize'],
        $expected['custom'],
        $expected['heartbeatPeriod'],
        $expected['slaveUuid']
    );

    expect($config->user)->toBe($expected['user']);
    expect($config->host)->toBe($expected['host']);
    expect($config->port)->toBe($expected['port']);
    expect($config->password)->toBe($expected['password']);
    expect($config->charset)->toBe($expected['charset']);
    expect($config->gtid)->toBe($expected['gtid']);
    expect($config->slaveId)->toBe($expected['slaveId']);
    expect($config->binLogFileName)->toBe($expected['binLogFileName']);
    expect($config->binLogPosition)->toBe($expected['binLogPosition']);
    expect($config->eventsOnly)->toBe($expected['eventsOnly']);
    expect($config->eventsIgnore)->toBe($expected['eventsIgnore']);
    expect($config->tablesOnly)->toBe($expected['tablesOnly']);
    expect($config->mariaDbGtid)->toBe($expected['mariaDbGtid']);
    expect($config->tableCacheSize)->toBe($expected['tableCacheSize']);
    expect($config->custom)->toBe($expected['custom']);
    expect($config->heartbeatPeriod)->toBe($expected['heartbeatPeriod']);
    expect($config->databasesOnly)->toBe($expected['databasesOnly']);
    expect($config->slaveUuid)->toBe($expected['slaveUuid']);

    $config->validate();
}

test('should check data bases only', function () {
    $config = (new ConfigBuilder())->withDatabasesOnly(['boo'])->build();
    expect($config->checkDataBasesOnly('foo'))->toBeTrue();

    $config = (new ConfigBuilder())->withDatabasesOnly(['foo'])->build();
    expect($config->checkDataBasesOnly('foo'))->toBeFalse();

    $config = (new ConfigBuilder())->withDatabasesOnly(['test'])->build();
    expect($config->checkDataBasesOnly('test'))->toBeFalse();

    $config = (new ConfigBuilder())->withDatabasesOnly(['foo'])->build();
    expect($config->checkDataBasesOnly('bar'))->toBeTrue();
});

test('should check tables only', function () {
    $config = (new ConfigBuilder())->build();
    expect($config->checkTablesOnly('foo'))->toBeFalse();

    $config = (new ConfigBuilder())->withTablesOnly(['foo'])->build();
    expect($config->checkTablesOnly('foo'))->toBeFalse();

    $config = (new ConfigBuilder())->withTablesOnly(['test'])->build();
    expect($config->checkTablesOnly('test'))->toBeFalse();

    $config = (new ConfigBuilder())->withTablesOnly(['foo'])->build();
    expect($config->checkTablesOnly('bar'))->toBeTrue();
});

test('should check event', function () {
    $config = (new ConfigBuilder())->build();
    expect($config->checkEvent(1))->toBeTrue();

    $config = (new ConfigBuilder())->withEventsOnly([['value' => 2]])->build();
    expect($config->checkEvent(2))->toBeTrue();

    $config = (new ConfigBuilder())->withEventsOnly([['value' => 3]])->build();
    expect($config->checkEvent(4))->toBeFalse();

    $config = (new ConfigBuilder())->withEventsIgnore([4])->build();
    expect($config->checkEvent(4))->toBeFalse();
});

dataset('shouldCheckHeartbeatPeriodProvider', function () {
    return [[0], [0.0], [0.001], [4294967], [2]];
});

test('should check heartbeat period', function (int|float $heartbeatPeriod) {
    $config = (new ConfigBuilder())->withHeartbeatPeriod($heartbeatPeriod)
        ->build();
    $config->validate();

    expect($config->heartbeatPeriod)->toEqual($heartbeatPeriod);
})->with('shouldCheckHeartbeatPeriodProvider');

dataset('shouldValidateProvider', function () {
    return [
        ['host', 'aaa', ConfigException::IP_ERROR_MESSAGE, ConfigException::IP_ERROR_CODE],
        ['port', -1, ConfigException::PORT_ERROR_MESSAGE, ConfigException::PORT_ERROR_CODE],
        ['slaveId', -1, ConfigException::SLAVE_ID_ERROR_MESSAGE, ConfigException::SLAVE_ID_ERROR_CODE],
        ['gtid', '-1', ConfigException::GTID_ERROR_MESSAGE, ConfigException::GTID_ERROR_CODE],
        [
            'binLogPosition',
            '-1',
            ConfigException::BIN_LOG_FILE_POSITION_ERROR_MESSAGE,
            ConfigException::BIN_LOG_FILE_POSITION_ERROR_CODE,
        ],
        [
            'tableCacheSize',
            -1,
            ConfigException::TABLE_CACHE_SIZE_ERROR_MESSAGE,
            ConfigException::TABLE_CACHE_SIZE_ERROR_CODE,
        ],
        [
            'heartbeatPeriod',
            4294968,
            ConfigException::HEARTBEAT_PERIOD_ERROR_MESSAGE,
            ConfigException::HEARTBEAT_PERIOD_ERROR_CODE,
        ],
        [
            'heartbeatPeriod',
            -1,
            ConfigException::HEARTBEAT_PERIOD_ERROR_MESSAGE,
            ConfigException::HEARTBEAT_PERIOD_ERROR_CODE,
        ],
    ];
});

test('should validate', function (string $configKey, mixed $configValue, string $expectedMessage, int $expectedCode) {
    $this->expectException(ConfigException::class);
    $this->expectExceptionMessage($expectedMessage);
    $this->expectExceptionCode($expectedCode);

    $config = (new ConfigBuilder())->{'with' . strtoupper($configKey)}($configValue)->build();
    $config->validate();
})->with('shouldValidateProvider');
