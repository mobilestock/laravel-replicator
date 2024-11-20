<?php

use Illuminate\Support\Facades\Config;
use MobileStock\LaravelReplicator\Config\ReplicationConfigManager;

beforeEach(function () {
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
                'interceptor' => [InterceptorClasseExample::class, 'translateUserId'],
            ],
        ]);
});

test('should load configurations correctly', function () {
    $manager = new ReplicationConfigManager();

    expect($manager->getConfigurations())->toBeArray()->toHaveKey('usuarios_to_users');
});

test('should retrieve unique databases correctly', function () {
    $manager = new ReplicationConfigManager();

    expect($manager->getDatabases())
        ->toBeArray()
        ->toEqualCanonicalizing(['legacy_database', 'users_api_database']);
});

test('should retrieve unique tables correctly', function () {
    $manager = new ReplicationConfigManager();

    expect($manager->getTables())
        ->toBeArray()
        ->toEqualCanonicalizing(['usuarios', 'users']);
});

test('should support interceptor functionality', function () {
    $interceptor = new InterceptorClasseExample();
    $data = ['id_usuario' => 1932, 'origin' => 'LEGACY'];
    $modifiedData = $interceptor->translateUserId($data);

    expect($modifiedData)->toBeArray()->toHaveKey('user_id', 1927);
});

test('should interceptor not change value', function () {
    $interceptor = new InterceptorClasseExample();
    $data = ['user_id' => 1927, 'origin' => 'USER-API'];
    $modifiedData = $interceptor->translateUserId($data);

    expect($modifiedData)->toBeArray()->toHaveKey('user_id', 1927);
});

class InterceptorClasseExample
{
    public function translateUserId(array $data): array
    {
        if ($data['origin'] === 'LEGACY') {
            $data['user_id'] = 1927;
        } else {
            $data['id_usuario'] = 1932;
        }
        return $data;
    }
}
