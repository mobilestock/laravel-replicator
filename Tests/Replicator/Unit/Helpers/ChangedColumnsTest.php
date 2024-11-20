<?php

use MobileStock\LaravelReplicator\Helpers\ChangedColumns;
use MySQLReplication\BinLog\BinLogCurrent;
use MySQLReplication\Event\DTO\DeleteRowsDTO;
use MySQLReplication\Event\DTO\UpdateRowsDTO;
use MySQLReplication\Event\DTO\WriteRowsDTO;
use MySQLReplication\Event\EventInfo;
use MySQLReplication\Event\RowEvent\ColumnDTO;
use MySQLReplication\Event\RowEvent\ColumnDTOCollection;
use MySQLReplication\Event\RowEvent\TableMap;
use MySQLReplication\Repository\FieldDTO;

test('should return changed columns on update', function () {
    $binLogCurrent = new BinLogCurrent();
    $eventInfo = new EventInfo(1699862400, 1, 1, 100, '12345', 0, true, $binLogCurrent);

    $columns = ['nome', 'email'];
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
            'before' => ['nome' => 'John Doe', 'email' => 'john.doe@example.com'],
            'after' => ['nome' => 'John Smith', 'email' => 'john.doe@example.com'],
        ],
    ]);

    $changedColumns = ChangedColumns::getChangedColumns($event);

    expect($changedColumns)->toEqual(['nome']);
});

test('should return all columns on write', function () {
    $binLogCurrent = new BinLogCurrent();
    $eventInfo = new EventInfo(1699862400, 1, 1, 100, '12345', 0, true, $binLogCurrent);

    $columns = ['name', 'email'];
    $columnDTOCollection = new ColumnDTOCollection(
        array_map(
            fn($col) => new ColumnDTO(
                new FieldDTO($col, 'users_api_db', 'users', 'VARCHAR', true, false),
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

    $tableMap = new TableMap('1', 'users_api_db', 'users', count($columns), $columnDTOCollection);

    $event = new WriteRowsDTO($eventInfo, $tableMap, 1, [['name' => 'John Doe', 'email' => 'john.doe@example.com']]);

    $changedColumns = ChangedColumns::getChangedColumns($event);

    expect($changedColumns)->toEqual(['name', 'email']);
});

test('should return columns on delete with before values', function () {
    $binLogCurrent = new BinLogCurrent();
    $eventInfo = new EventInfo(1699862400, 1, 1, 100, '12345', 0, true, $binLogCurrent);

    $columns = ['nome', 'email'];
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
        'before' => ['nome' => 'John Doe', 'email' => 'john.doe@example.com'],
    ]);

    $changedColumns = ChangedColumns::getChangedColumns($event);

    expect($changedColumns)->toEqual(['nome', 'email']);
});

test('should return empty array when no changes on delete without values', function () {
    $binLogCurrent = new BinLogCurrent();
    $eventInfo = new EventInfo(1699862400, 1, 1, 100, '12345', 0, true, $binLogCurrent);

    $columns = ['name', 'email'];
    $columnDTOCollection = new ColumnDTOCollection(
        array_map(
            fn($col) => new ColumnDTO(
                new FieldDTO($col, 'users_api_db', 'usuarios', 'VARCHAR', true, false),
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

    $tableMap = new TableMap('1', 'users_api_db', 'usuarios', count($columns), $columnDTOCollection);

    $event = new DeleteRowsDTO($eventInfo, $tableMap, 1, []);

    $changedColumns = ChangedColumns::getChangedColumns($event);

    expect($changedColumns)->toEqual([]);
});
