<?php

use MobileStock\LaravelReplicator\Database\DatabaseService;
use MobileStock\LaravelReplicator\Handlers\DeleteHandler;
use MobileStock\LaravelReplicator\Handlers\InsertHandler;
use MobileStock\LaravelReplicator\Handlers\UpdateHandler;
use MobileStock\LaravelReplicator\Helpers\ChangedColumns;
use MobileStock\LaravelReplicator\Interceptor\InterceptorManager;
use MobileStock\LaravelReplicator\Subscribers\Registration;
use MySQLReplication\BinLog\BinLogCurrent;
use MySQLReplication\Event\DTO\DeleteRowsDTO;
use MySQLReplication\Event\DTO\UpdateRowsDTO;
use MySQLReplication\Event\DTO\WriteRowsDTO;
use MySQLReplication\Event\EventInfo;
use MySQLReplication\Event\RowEvent\TableMap;

beforeEach(function () {
    Mockery::close();
});

test('should handle WriteRowsDTO event', function () {
    $configurations = [
        'usuarios_to_users' => [
            'node_primary' => [
                'database' => 'legacy_database',
                'table' => 'usuarios',
                'reference_key' => 'id_usuario',
            ],
            'node_secondary' => [
                'database' => 'users_api_database',
                'table' => 'users',
                'reference_key' => 'user_id',
            ],
            'columns' => [
                'id_usuario' => 'user_id',
            ],
            'interceptor' => [InterceptorManager::class, 'applyInterceptor'],
        ],
    ];

    $binLogCurrent = Mockery::mock(BinLogCurrent::class)
        ->shouldReceive('getBinFileName')
        ->andReturn('binlog.000001')
        ->shouldReceive('getBinLogPosition')
        ->andReturn(12345)
        ->getMock();

    $eventInfo = Mockery::mock(EventInfo::class)
        ->shouldReceive('binLogCurrent')
        ->andReturn($binLogCurrent)
        ->getMock();

    $tableMap = Mockery::mock(TableMap::class)
        ->shouldReceive('database')
        ->andReturn('legacy_database')
        ->shouldReceive('table')
        ->andReturn('usuarios')
        ->getMock();

    $event = Mockery::mock(WriteRowsDTO::class, [$eventInfo, $tableMap, 1, [['id_usuario' => 123]]])
        ->shouldReceive('values')
        ->andReturn([['id_usuario' => 123]])
        ->getMock();

    Mockery::mock('alias:' . ChangedColumns::class)
        ->shouldReceive('getChangedColumns')
        ->andReturn(['id_usuario']);

    Mockery::mock('alias:' . InsertHandler::class)
        ->shouldReceive('handle')
        ->once()
        ->with('users_api_database', 'users', ['id_usuario' => 'user_id'], ['id_usuario' => 123]);

    Mockery::mock('alias:' . DatabaseService::class)
        ->shouldReceive('updateBinlogPosition')
        ->once()
        ->with('binlog.000001', 12345);

    $registration = new Registration($configurations);
    $registration->allEvents($event);

    expect(true)->toBeTrue();
});

test('should handle UpdateRowsDTO event', function () {
    $configurations = [
        'usuarios_to_users' => [
            'node_primary' => [
                'database' => 'legacy_database',
                'table' => 'usuarios',
                'reference_key' => 'id_usuario',
            ],
            'node_secondary' => [
                'database' => 'users_api_database',
                'table' => 'users',
                'reference_key' => 'user_id',
            ],
            'columns' => [
                'id_usuario' => 'user_id',
            ],
            'interceptor' => [InterceptorManager::class, 'applyInterceptor'],
        ],
    ];

    $binLogCurrent = Mockery::mock(BinLogCurrent::class)
        ->shouldReceive('getBinFileName')
        ->andReturn('binlog.000001')
        ->shouldReceive('getBinLogPosition')
        ->andReturn(12345)
        ->getMock();

    $eventInfo = Mockery::mock(EventInfo::class)
        ->shouldReceive('binLogCurrent')
        ->andReturn($binLogCurrent)
        ->getMock();

    $tableMap = Mockery::mock(TableMap::class)
        ->shouldReceive('database')
        ->andReturn('legacy_database')
        ->shouldReceive('table')
        ->andReturn('usuarios')
        ->getMock();

    $event = Mockery::mock(UpdateRowsDTO::class, [
        $eventInfo,
        $tableMap,
        1,
        [['before' => ['id_usuario' => 123], 'after' => ['id_usuario' => 124]]],
    ])
        ->shouldReceive('values')
        ->andReturn([['before' => ['id_usuario' => 123], 'after' => ['id_usuario' => 124]]])
        ->getMock();

    Mockery::mock('alias:' . ChangedColumns::class)
        ->shouldReceive('getChangedColumns')
        ->andReturn(['id_usuario']);

    Mockery::mock('alias:' . UpdateHandler::class)
        ->shouldReceive('handle')
        ->once()
        ->with(
            'id_usuario',
            'users_api_database',
            'users',
            'user_id',
            ['id_usuario' => 'user_id'],
            ['before' => ['id_usuario' => 123], 'after' => ['id_usuario' => 124]]
        );

    Mockery::mock('alias:' . DatabaseService::class)
        ->shouldReceive('updateBinlogPosition')
        ->once()
        ->with('binlog.000001', 12345);

    $registration = new Registration($configurations);
    $registration->allEvents($event);

    expect(true)->toBeTrue();
});

test('should handle DeleteRowsDTO event', function () {
    $configurations = [
        'usuarios_to_users' => [
            'node_primary' => [
                'database' => 'legacy_database',
                'table' => 'usuarios',
                'reference_key' => 'id_usuario',
            ],
            'node_secondary' => [
                'database' => 'users_api_database',
                'table' => 'users',
                'reference_key' => 'user_id',
            ],
            'columns' => [
                'id_usuario' => 'user_id',
            ],
        ],
    ];

    $binLogCurrent = Mockery::mock(BinLogCurrent::class)
        ->shouldReceive('getBinFileName')
        ->andReturn('binlog.000001')
        ->shouldReceive('getBinLogPosition')
        ->andReturn(12345)
        ->getMock();

    $eventInfo = Mockery::mock(EventInfo::class)
        ->shouldReceive('binLogCurrent')
        ->andReturn($binLogCurrent)
        ->getMock();

    $tableMap = Mockery::mock(TableMap::class)
        ->shouldReceive('database')
        ->andReturn('legacy_database')
        ->shouldReceive('table')
        ->andReturn('usuarios')
        ->getMock();

    $event = Mockery::mock(DeleteRowsDTO::class, [$eventInfo, $tableMap, 1, [['id_usuario' => 123]]])
        ->shouldReceive('values')
        ->andReturn([['id_usuario' => 123]])
        ->getMock();

    Mockery::mock('alias:' . ChangedColumns::class)
        ->shouldReceive('getChangedColumns')
        ->andReturn(['id_usuario']);

    Mockery::mock('alias:' . DeleteHandler::class)
        ->shouldReceive('handle')
        ->once()
        ->with('users_api_database', 'users', 'id_usuario', 'user_id', ['id_usuario' => 123]);

    Mockery::mock('alias:' . DatabaseService::class)
        ->shouldReceive('updateBinlogPosition')
        ->once()
        ->with('binlog.000001', 12345);

    $registration = new Registration($configurations);
    $registration->allEvents($event);

    expect(true)->toBeTrue();
});
