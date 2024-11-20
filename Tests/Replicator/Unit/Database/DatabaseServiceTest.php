<?php

use Illuminate\Support\Facades\DB;
use MobileStock\LaravelReplicator\Database\DatabaseService;

beforeEach(function () {
    Mockery::close();
    putenv('REPLICATOR_DB=replicator_test_db');
});

test('should call DB::update with correct query and binds', function () {
    $database = 'users_api_db';
    $table = 'users';
    $clausule = 'name = :name';
    $referenceKey = 'user_id';
    $binds = [':name' => 'Capi Bara', ':user_id' => 1932];

    $expectedSql = "UPDATE {$database}.{$table} SET {$clausule} WHERE {$database}.{$table}.{$referenceKey} = :{$referenceKey} /* isReplicating */;";

    DB::shouldReceive('update')->once()->with($expectedSql, $binds)->andReturnTrue();

    $databaseService = new DatabaseService();
    $databaseService->update($database, $table, $clausule, $referenceKey, $binds);

    expect(true)->toBeTrue();
});

test('should call DB::insert with correct query and binds', function () {
    $database = 'users_api_database';
    $table = 'users';
    $columns = 'name,email';
    $placeholders = ':name,:email';
    $binds = [':name' => 'Rick Sanches', ':email' => 'little_rick@galaticfederation.gov'];

    $expectedSql = "INSERT INTO {$database}.{$table} ({$columns}) VALUES ({$placeholders}) /* isReplicating */;";

    DB::shouldReceive('insert')->once()->with($expectedSql, $binds)->andReturnTrue();

    $databaseService = new DatabaseService();
    $databaseService->insert($database, $table, $columns, $placeholders, $binds);

    expect(true)->toBeTrue();
});

test('should call DB::delete with correct query and binds', function () {
    $database = 'users_api_database';
    $table = 'users';
    $referenceKey = 'user_id';
    $binds = [':user_id' => 1932];

    $expectedSql = "DELETE FROM {$database}.{$table} WHERE {$database}.{$table}.{$referenceKey} = :{$referenceKey} /* isReplicating */;";

    DB::shouldReceive('delete')->once()->with($expectedSql, $binds)->andReturnTrue();

    $databaseService = new DatabaseService();
    $databaseService->delete($database, $table, $referenceKey, $binds);

    expect(true)->toBeTrue();
});

// TODO: esse teste vai precisar ser ajustado quando o pdointercept for instalado
test('should return correct binlog position from DB::selectOne', function () {
    $expectedBinlogPosition = ['file' => 'binlog.000001', 'position' => 123456];
    DB::shouldReceive('selectOne')
        ->once()
        ->with('SELECT json_binlog FROM replicator_test_db.settings')
        ->andReturn((object) ['json_binlog' => json_encode($expectedBinlogPosition)]);

    $databaseService = new DatabaseService();
    $binlogPosition = $databaseService->getLastBinlogPosition();

    expect($binlogPosition)->toEqual($expectedBinlogPosition);
});

test('should return null when no binlog position is found in DB::selectOne', function () {
    DB::shouldReceive('selectOne')
        ->once()
        ->with('SELECT json_binlog FROM replicator_test_db.settings')
        ->andReturnNull();

    $databaseService = new DatabaseService();
    $binlogPosition = $databaseService->getLastBinlogPosition();

    expect($binlogPosition)->toBeNull();
});

// TODO: esse teste vai precisar ser ajustado quando o pdointercept for instalado
test('should call DB::update to update binlog position', function () {
    $fileName = 'binlog.000002';
    $position = 789012;

    $expectedJsonBinlog = json_encode(['file' => $fileName, 'position' => $position]);
    $expectedSql = 'UPDATE replicator_test_db.settings SET json_binlog = :json_binlog WHERE true;';

    DB::shouldReceive('update')
        ->once()
        ->with($expectedSql, [
            'json_binlog' => $expectedJsonBinlog,
        ])
        ->andReturnTrue();

    $databaseService = new DatabaseService();
    $databaseService->updateBinlogPosition($fileName, $position);

    expect(true)->toBeTrue();
});

afterEach(function () {
    Mockery::close();
    putenv('REPLICATOR_DB');
});
