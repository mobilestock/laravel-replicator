<?php

use Illuminate\Support\Facades\DB;
use MobileStock\LaravelReplicator\Handlers\UpdateHandler;

test('should successfully update data when changes are detected', function () {
    $nodePrimaryReferenceKey = 'id_usuario';
    $nodeSecondaryDatabase = 'users_api_database';
    $nodeSecondaryTable = 'users';
    $nodeSecondaryReferenceKey = 'user_id';
    $columnMappings = ['nome' => 'name', 'email' => 'email_address'];
    $row = [
        'before' => ['id_usuario' => 1932, 'nome' => 'John Doe', 'email' => 'john.doe@example.com'],
        'after' => ['id_usuario' => 1932, 'nome' => 'John Smith', 'email' => 'john.smith@example.com'],
    ];

    $expectedSql = "UPDATE {$nodeSecondaryDatabase}.{$nodeSecondaryTable} SET {$nodeSecondaryDatabase}.{$nodeSecondaryTable}.name = :name, {$nodeSecondaryDatabase}.{$nodeSecondaryTable}.email_address = :email_address WHERE {$nodeSecondaryDatabase}.{$nodeSecondaryTable}.user_id = :user_id /* isReplicating */;";
    $expectedBinds = [
        ':name' => 'John Smith',
        ':email_address' => 'john.smith@example.com',
        ':user_id' => 1932,
    ];

    DB::shouldReceive('update')->once()->with($expectedSql, $expectedBinds)->andReturnTrue();

    UpdateHandler::handle(
        $nodePrimaryReferenceKey,
        $nodeSecondaryDatabase,
        $nodeSecondaryTable,
        $nodeSecondaryReferenceKey,
        $columnMappings,
        $row
    );

    expect(true)->toBeTrue();
});

test('should not perform update when no changes are detected', function () {
    $nodePrimaryReferenceKey = 'id_usuario';
    $nodeSecondaryDatabase = 'users_api_database';
    $nodeSecondaryTable = 'users';
    $nodeSecondaryReferenceKey = 'user_id';
    $columnMappings = ['nome' => 'name', 'email' => 'email_address'];
    $row = [
        'before' => ['id_usuario' => 1932, 'nome' => 'John Doe', 'email' => 'john.doe@example.com'],
        'after' => ['id_usuario' => 1932, 'nome' => 'John Doe', 'email' => 'john.doe@example.com'],
    ];

    DB::shouldReceive('update')->never();

    UpdateHandler::handle(
        $nodePrimaryReferenceKey,
        $nodeSecondaryDatabase,
        $nodeSecondaryTable,
        $nodeSecondaryReferenceKey,
        $columnMappings,
        $row
    );

    expect(true)->toBeTrue();
});
