<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use MobileStock\LaravelReplicator\Console\Commands\StartReplicationCommand;
use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Event\EventSubscribers;
use MySQLReplication\MySQLReplicationFactory;

test('should configure replication and execute handle method correctly', function () {
    putenv('DB_HOST=127.0.0.1');
    putenv('DB_PORT=3306');
    putenv('REPLICATOR_DB_USERNAME=root');
    putenv('REPLICATOR_DB_PASSWORD=toor');
    putenv('REPLICATOR_DB=replicator_test_db');

    Config::shouldReceive('get')
        ->once()
        ->with('replicator')
        ->andReturn([
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
        ]);

    DB::shouldReceive('selectOne')
        ->once()
        ->andReturn((object) ['json_binlog' => json_encode(['file' => 'binlog_file', 'position' => 12345])]);

    DB::shouldReceive('update')->andReturnUsing(function () {
        return 1;
    });

    DB::shouldReceive('insert')->andReturnUsing(function () {
        return true;
    });

    DB::shouldReceive('delete')->andReturnUsing(function () {
        return 1;
    });

    Mockery::mock('overload:' . ConfigBuilder::class)
        ->shouldReceive('withHost')
        ->andReturnSelf()
        ->shouldReceive('withPort')
        ->andReturnSelf()
        ->shouldReceive('withUser')
        ->andReturnSelf()
        ->shouldReceive('withPassword')
        ->andReturnSelf()
        ->shouldReceive('withEventsOnly')
        ->andReturnSelf()
        ->shouldReceive('withDatabasesOnly')
        ->andReturnSelf()
        ->shouldReceive('withTablesOnly')
        ->andReturnSelf()
        ->shouldReceive('withBinLogFileName')
        ->andReturnSelf()
        ->shouldReceive('withBinLogPosition')
        ->andReturnSelf()
        ->shouldReceive('build')
        ->andReturn('mocked_build');

    Mockery::mock('overload:' . MySQLReplicationFactory::class)
        ->shouldReceive('registerSubscriber')
        ->once()
        ->andReturnNull()
        ->shouldReceive('run')
        ->once()
        ->andReturnNull();

    Mockery::mock('overload:' . EventSubscribers::class)
        ->shouldReceive('allEvents')
        ->andReturnNull();

    $command = app(StartReplicationCommand::class);
    $command->handle();

    expect(true)->toBeTrue();
});
