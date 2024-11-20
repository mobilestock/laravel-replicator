<?php

use Illuminate\Support\Facades\DB;
use MobileStock\LaravelReplicator\Handlers\InsertHandler;

test('should successfully insert data with column mappings using InsertHandler', function () {
    $nodeSecondaryDatabase = 'users_api_database';
    $nodeSecondaryTable = 'users';
    $columnMappings = ['id_usuario' => 'user_id', 'nome' => 'name'];
    $data = ['id_usuario' => 1932, 'nome' => 'Steve Musk'];

    $expectedSql = "INSERT INTO {$nodeSecondaryDatabase}.{$nodeSecondaryTable} (user_id,name) VALUES (:user_id,:name) /* isReplicating */;";
    $expectedBinds = [
        ':user_id' => 1932,
        ':name' => 'Steve Musk',
    ];

    DB::shouldReceive('insert')->once()->with($expectedSql, $expectedBinds)->andReturnTrue();

    InsertHandler::handle($nodeSecondaryDatabase, $nodeSecondaryTable, $columnMappings, $data);

    expect(true)->toBeTrue();
});

test('should successfully insert data without column mappings using InsertHandler', function () {
    $nodeSecondaryDatabase = 'users_api_database';
    $nodeSecondaryTable = 'users';
    $columnMappings = [];
    $data = ['id_usuario' => 1932, 'nome' => 'Elon Jobs'];

    $expectedSql = "INSERT INTO {$nodeSecondaryDatabase}.{$nodeSecondaryTable} (id_usuario,nome) VALUES (:id_usuario,:nome) /* isReplicating */;";
    $expectedBinds = [
        ':id_usuario' => 1932,
        ':nome' => 'Elon Jobs',
    ];

    DB::shouldReceive('insert')->once()->with($expectedSql, $expectedBinds)->andReturnTrue();

    InsertHandler::handle($nodeSecondaryDatabase, $nodeSecondaryTable, $columnMappings, $data);

    expect(true)->toBeTrue();
});
