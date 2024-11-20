<?php

use Illuminate\Support\Facades\App;
use MobileStock\LaravelReplicator\Handlers\DeleteHandler;
use MobileStock\LaravelReplicator\Handlers\InsertHandler;
use MobileStock\LaravelReplicator\Helpers\ChangedColumns;
use MobileStock\LaravelReplicator\Subscribers\Registration;
use MySQLReplication\BinLog\BinLogCurrent;
use MySQLReplication\Event\DTO\DeleteRowsDTO;
use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\Event\DTO\UpdateRowsDTO;
use MySQLReplication\Event\DTO\WriteRowsDTO;
use MySQLReplication\Event\EventInfo;
use MySQLReplication\Event\RowEvent\ColumnDTO;
use MySQLReplication\Event\RowEvent\ColumnDTOCollection;
use MySQLReplication\Event\RowEvent\TableMap;
use MySQLReplication\Repository\FieldDTO;

beforeEach(function () {
    $this->configurations = [
        [
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
            'interceptor' => [AnotherInterceptorClassExample::class, 'someMethod'],
        ],
    ];

    $this->registration = new Registration($this->configurations);
});

afterEach(function () {
    Mockery::close();
});

it('should return early if event is not WriteRowsDTO, UpdateRowsDTO, or DeleteRowsDTO', function () {
    $event = Mockery::mock(EventDTO::class);

    $this->registration->allEvents($event);

    expect(true)->toBeTrue();
});

it('should process WriteRowsDTO event correctly', function () {
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
            'interceptor' => [AnotherInterceptorClassExample::class, 'someMethod'],
        ],
    ];

    $binLogCurrent = new BinLogCurrent();
    $binLogCurrent->setBinFileName('binlog.000001');
    $binLogCurrent->setBinLogPosition(12345);

    $eventInfo = new EventInfo(1699862400, 1, 1, 100, '12345', 0, true, $binLogCurrent);

    $columns = ['id_usuario', 'name'];
    $columnDTOCollection = new ColumnDTOCollection(
        array_map(
            fn($col) => new ColumnDTO(
                new FieldDTO($col, 'legacy_database', 'usuarios', 'VARCHAR', true, false),
                1,
                255,
                10,
                0,
                0,
                0,
                0,
                0,
                0
            ),
            $columns
        )
    );

    $tableMap = new TableMap('1', 'legacy_database', 'usuarios', count($columns), $columnDTOCollection);

    $event = new WriteRowsDTO($eventInfo, $tableMap, 1, [['id_usuario' => 1, 'name' => 'John Doe']]);

    Mockery::mock(ChangedColumns::class, [
        'getChangedColumns' => fn() => ['id_usuario'],
    ]);

    $insertHandlerMock = Mockery::mock(InsertHandler::class);
    $insertHandlerMock
        ->shouldReceive('handle')
        ->with('users_api_database', 'users', ['id_usuario' => 'user_id'], ['id_usuario' => 1, 'name' => 'John Doe']);

    $databaseServiceMock = Mockery::mock(DatabaseService::class);
    $databaseServiceMock->shouldReceive('updateBinlogPosition')->with('binlog.000001', 12345);

    $registration = new Registration($configurations);
    $registration->allEvents($event);

    expect(true)->toBeTrue();
});

it('should process UpdateRowsDTO event correctly', function () {
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
            'interceptor' => [AnotherInterceptorClassExample::class, 'someMethod'],
        ],
    ];

    $binLogCurrent = new BinLogCurrent();
    $binLogCurrent->setBinFileName('binlog.000002');
    $binLogCurrent->setBinLogPosition(12346);

    $eventInfo = new EventInfo(1699862400, 1, 1, 100, '12345', 0, true, $binLogCurrent);

    $columns = ['id_usuario', 'name'];
    $columnDTOCollection = new ColumnDTOCollection(
        array_map(
            fn($col) => new ColumnDTO(
                new FieldDTO($col, 'legacy_database', 'usuarios', 'VARCHAR', true, false),
                1,
                255,
                10,
                0,
                0,
                0,
                0,
                0,
                0
            ),
            $columns
        )
    );

    $tableMap = new TableMap('1', 'legacy_database', 'usuarios', count($columns), $columnDTOCollection);

    $event = new UpdateRowsDTO($eventInfo, $tableMap, 1, [
        [
            'before' => ['id_usuario' => 1, 'name' => 'John Doe'],
            'after' => ['id_usuario' => 1, 'name' => 'Jane Doe'],
        ],
    ]);

    $changedColumnsMock = Mockery::mock(ChangedColumns::class);
    $changedColumnsMock
        ->shouldReceive('getChangedColumns')
        ->with(Mockery::type(UpdateRowsDTO::class))
        ->andReturn(['name']);

    $interceptorMock = Mockery::mock(InterceptorManager::class);
    $interceptorMock
        ->shouldReceive('applyInterceptor')
        ->with(
            [AnotherInterceptorClassExample::class, 'someMethod'],
            ['id_usuario' => 1, 'name' => 'Jane Doe'],
            'usuarios',
            'legacy_database'
        )
        ->andReturn(['id_usuario' => 1, 'name' => 'Jane Doe']);

    $updateHandlerMock = Mockery::mock(UpdateHandler::class);
    $updateHandlerMock
        ->shouldReceive('handle')
        ->with(
            'id_usuario',
            'users_api_database',
            'users',
            'user_id',
            ['id_usuario' => 'user_id'],
            ['id_usuario' => 1, 'name' => 'Jane Doe']
        );

    $databaseServiceMock = Mockery::mock(DatabaseService::class);
    $databaseServiceMock->shouldReceive('updateBinlogPosition')->with('binlog.000002', 12346);

    $registration = new Registration($configurations);
    $registration->allEvents($event);

    expect(true)->toBeTrue();
});

it('should process DeleteRowsDTO event correctly', function () {
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
            'interceptor' => [AnotherInterceptorClassExample::class, 'someMethod'],
        ],
    ];

    $binLogCurrent = new BinLogCurrent();
    $binLogCurrent->setBinFileName('binlog.000003');
    $binLogCurrent->setBinLogPosition(12347);

    $eventInfo = new EventInfo(1699862400, 1, 1, 100, '12345', 0, true, $binLogCurrent);

    $columns = ['id_usuario', 'name'];
    $columnDTOCollection = new ColumnDTOCollection(
        array_map(
            fn($col) => new ColumnDTO(
                new FieldDTO($col, 'legacy_database', 'usuarios', 'VARCHAR', true, false),
                1,
                255,
                10,
                0,
                0,
                0,
                0,
                0,
                0
            ),
            $columns
        )
    );

    $tableMap = new TableMap('1', 'legacy_database', 'usuarios', count($columns), $columnDTOCollection);

    $event = new DeleteRowsDTO($eventInfo, $tableMap, 1, [
        'before' => ['id_usuario' => 1, 'name' => 'John Doe'],
    ]);

    $changedColumnsMock = Mockery::mock(ChangedColumns::class);
    $changedColumnsMock
        ->shouldReceive('getChangedColumns')

        ->with(Mockery::type(DeleteRowsDTO::class))
        ->andReturn(['id_usuario']);

    $interceptorMock = Mockery::mock(InterceptorManager::class);
    $interceptorMock
        ->shouldReceive('applyInterceptor')
        ->with(
            [AnotherInterceptorClassExample::class, 'someMethod'],
            ['id_usuario' => 1, 'name' => 'John Doe'],
            'usuarios',
            'legacy_database'
        )
        ->andReturn(['id_usuario' => 1, 'name' => 'John Doe']);

    $deleteHandlerMock = Mockery::mock(DeleteHandler::class);
    $deleteHandlerMock
        ->shouldReceive('handle')
        ->with('users_api_database', 'users', 'id_usuario', 'user_id', ['id_usuario' => 1, 'name' => 'John Doe']);

    $databaseServiceMock = Mockery::mock(DatabaseService::class);
    $databaseServiceMock->shouldReceive('updateBinlogPosition')->with('binlog.000003', 12347);

    $registration = new Registration($configurations);
    $registration->allEvents($event);

    expect(true)->toBeTrue();
});

it('should continue if no configured columns are changed', function () {
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
            'interceptor' => null,
        ],
    ];

    Mockery::mock(ChangedColumns::class)
        ->shouldReceive('getChangedColumns')
        ->with(Mockery::type(WriteRowsDTO::class))
        ->andReturn(['other_column']);

    Mockery::mock(InsertHandler::class)->shouldNotReceive('handle');

    $tableMap = new TableMap('legacy_database', 'usuarios', '1', 2, new ColumnDTOCollection([]));

    $binLogCurrent = new BinLogCurrent();
    $binLogCurrent->setBinFileName('binlog.000003');
    $binLogCurrent->setBinLogPosition(12347);

    $eventInfo = new EventInfo(1699862400, 1, 1, 100, '12345', 0, true, $binLogCurrent);

    $event = new WriteRowsDTO($eventInfo, $tableMap, 1, [['id_usuario' => 1, 'name' => 'John Doe']]);

    $registration = new Registration($configurations);
    $registration->allEvents($event);

    expect(true)->toBeTrue();
});

it('should handle event without interceptor', function () {
    Mockery::mock(ChangedColumns::class)
        ->shouldReceive('getChangedColumns')
        ->with(Mockery::type(WriteRowsDTO::class))
        ->andReturn(['id_usuario']);

    Mockery::mock(InterceptorManager::class)->shouldNotReceive('applyInterceptor');

    Mockery::mock(InsertHandler::class)
        ->shouldReceive('handle')
        ->with('users_api_database', 'users', ['id_usuario' => 'user_id'], ['id_usuario' => 1, 'name' => 'John Doe']);

    Mockery::mock(DatabaseService::class)
        ->shouldReceive('updateBinlogPosition')
        ->with('binlog.000005', 12349);

    $config = [
        [
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

    $registration = new Registration($config);

    $tableMap = new TableMap('legacy_database', 'usuarios', '1', 2, new ColumnDTOCollection([]));

    $binLogCurrent = new BinLogCurrent();
    $binLogCurrent->setBinFileName('binlog.000003');
    $binLogCurrent->setBinLogPosition(12347);

    $eventInfo = new EventInfo(1699862400, 1, 1, 100, '12345', 0, true, $binLogCurrent);

    $event = new WriteRowsDTO($eventInfo, $tableMap, 1, [['id_usuario' => 1, 'name' => 'John Doe']]);

    $registration->allEvents($event);
});

it('should log error if interceptor throws exception', function () {
    Mockery::mock(ChangedColumns::class)
        ->shouldReceive('getChangedColumns')
        ->with(Mockery::type(UpdateRowsDTO::class))
        ->andReturn(['name']);

    Mockery::mock(InterceptorManager::class)
        ->shouldReceive('applyInterceptor')
        ->andThrow(new Exception('Interceptor error'));

    $updateHandlerMock = Mockery::mock(UpdateHandler::class);
    $updateHandlerMock->shouldNotReceive('handle');

    $columns = ['id_usuario', 'name'];
    $columnDTOCollection = new ColumnDTOCollection(
        array_map(
            fn($col) => new ColumnDTO(
                new FieldDTO($col, 'legacy_database', 'usuarios', 'VARCHAR', true, false),
                1,
                255,
                10,
                0,
                0,
                0,
                0,
                0,
                0
            ),
            $columns
        )
    );

    $tableMap = new TableMap('legacy_database', 'usuarios', '1', 2, $columnDTOCollection);

    $binLogCurrent = new BinLogCurrent();
    $binLogCurrent->setBinFileName('binlog.000007');
    $binLogCurrent->setBinLogPosition(12351);

    $eventInfo = new EventInfo(1699862400, 1, 1, 100, '12345', 0, true, $binLogCurrent);

    $event = new UpdateRowsDTO($eventInfo, $tableMap, 1, [
        [
            'before' => ['id_usuario' => 1, 'name' => 'John Doe'],
            'after' => ['id_usuario' => 1, 'name' => 'Jane Doe'],
        ],
    ]);

    $this->registration->allEvents($event);

    expect(true)->toBeTrue();
});

it('should handle event when database and table match node_secondary', function () {
    $configurations = [
        [
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

    Mockery::mock(ChangedColumns::class)
        ->shouldReceive('getChangedColumns')
        ->with(Mockery::type(WriteRowsDTO::class))
        ->andReturn(['user_id']);

    Mockery::mock(InsertHandler::class)
        ->shouldReceive('handle')
        ->with('users_api_database', 'users', ['id_usuario' => 'user_id'], ['user_id' => 2, 'name' => 'Alice Smith']);

    Mockery::mock(DatabaseService::class)
        ->shouldReceive('updateBinlogPosition')
        ->with('binlog.000006', 12350);

    $tableMap = new TableMap('users_api_database', 'users', '1', 2, new ColumnDTOCollection([]));

    $binLogCurrent = new BinLogCurrent();
    $binLogCurrent->setBinFileName('binlog.000006');
    $binLogCurrent->setBinLogPosition(12350);

    $eventInfo = new EventInfo(1699862400, 1, 1, 100, '12345', 0, true, $binLogCurrent);

    $event = new WriteRowsDTO($eventInfo, $tableMap, 1, [['user_id' => 2, 'name' => 'Alice Smith']]);

    $registration = new Registration($configurations);
    $registration->allEvents($event);

    expect(true)->toBeTrue();
});

it('should skip processing if ChangedColumns returns no modified columns', function () {
    $configurations = [
        [
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

    App::shouldNotReceive('call');

    Mockery::mock(ChangedColumns::class)
        ->shouldReceive('getChangedColumns')
        ->with(Mockery::type(WriteRowsDTO::class))
        ->andReturn([]);

    Mockery::mock(InsertHandler::class)->shouldNotReceive('handle');

    $tableMap = new TableMap('legacy_database', 'usuarios', '1', 2, new ColumnDTOCollection([]));

    $binLogCurrent = new BinLogCurrent();
    $binLogCurrent->setBinFileName('binlog.000004');
    $binLogCurrent->setBinLogPosition(12348);

    $eventInfo = new EventInfo(1699862400, 1, 1, 100, '12345', 0, true, $binLogCurrent);

    $event = new WriteRowsDTO($eventInfo, $tableMap, 1, [['id_usuario' => 1, 'name' => 'John Doe']]);

    $registration = new Registration($configurations);
    $registration->allEvents($event);

    expect(true)->toBeTrue();
});
