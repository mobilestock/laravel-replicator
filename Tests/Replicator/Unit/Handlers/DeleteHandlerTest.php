<?php

use Illuminate\Support\Facades\DB;
use MobileStock\LaravelReplicator\Handlers\DeleteHandler;

test('should method successfully deletes data', function () {
    putenv('REPLICATOR_DB=replicator');

    $nodeSecondaryDatabase = 'users_api_db';
    $nodeSecondaryTable = 'users';
    $nodePrimaryReferenceKey = 'id';
    $nodeSecondaryReferenceKey = 'user_id';
    $data = ['id' => 1932];

    DB::shouldReceive('delete')
        ->once()
        ->with('DELETE FROM users_api_db.users WHERE users_api_db.users.user_id = :user_id /* isReplicating */;', [
            ':user_id' => 1932,
        ])
        ->andReturnNull();

    DeleteHandler::handle(
        $nodeSecondaryDatabase,
        $nodeSecondaryTable,
        $nodePrimaryReferenceKey,
        $nodeSecondaryReferenceKey,
        $data
    );

    expect(true)->toBeTrue();
});

test('should successfully delete data using DeleteHandler', function () {
    $nodeSecondaryDatabase = 'users_api_database';
    $nodeSecondaryTable = 'users';
    $nodePrimaryReferenceKey = 'id_usuario';
    $nodeSecondaryReferenceKey = 'user_id';
    $data = ['id_usuario' => 1932];

    $expectedSql = "DELETE FROM {$nodeSecondaryDatabase}.{$nodeSecondaryTable} WHERE {$nodeSecondaryDatabase}.{$nodeSecondaryTable}.{$nodeSecondaryReferenceKey} = :{$nodeSecondaryReferenceKey} /* isReplicating */;";
    $expectedBinds = [':user_id' => 1932];

    DB::shouldReceive('delete')->once()->with($expectedSql, $expectedBinds)->andReturnTrue();

    DeleteHandler::handle(
        $nodeSecondaryDatabase,
        $nodeSecondaryTable,
        $nodePrimaryReferenceKey,
        $nodeSecondaryReferenceKey,
        $data
    );

    expect(true)->toBeTrue();
});
