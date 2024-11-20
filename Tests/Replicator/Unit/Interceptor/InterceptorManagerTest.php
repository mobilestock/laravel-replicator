<?php

use Illuminate\Support\Facades\App;
use MobileStock\LaravelReplicator\Interceptor\InterceptorManager;

test('should call interceptor and modify data correctly', function () {
    $interceptorFunction = ['TestInterceptorClass', 'transform'];
    $data = ['name' => 'John Doe', 'email' => 'john.doe@example.com'];
    $nodePrimaryTable = 'usuarios';
    $nodePrimaryDatabase = 'legacy_database';

    App::shouldReceive('call')
        ->once()
        ->with($interceptorFunction, [
            'data' => $data,
            'nodePrimaryTable' => $nodePrimaryTable,
            'nodePrimaryDatabase' => $nodePrimaryDatabase,
        ])
        ->andReturn(['name' => 'John Smith', 'email' => 'john.smith@example.com']);

    $result = InterceptorManager::applyInterceptor(
        $interceptorFunction,
        $data,
        $nodePrimaryTable,
        $nodePrimaryDatabase
    );

    expect($result)->toEqual(['name' => 'John Smith', 'email' => 'john.smith@example.com']);
});

test('should return original data when interceptor does not modify it', function () {
    $interceptorFunction = ['TestInterceptorClass', 'noop'];
    $data = ['name' => 'Jane Doe', 'email' => 'jane.doe@example.com'];
    $nodePrimaryTable = 'users';
    $nodePrimaryDatabase = 'users_api_database';

    App::shouldReceive('call')
        ->once()
        ->with($interceptorFunction, [
            'data' => $data,
            'nodePrimaryTable' => $nodePrimaryTable,
            'nodePrimaryDatabase' => $nodePrimaryDatabase,
        ])
        ->andReturn($data);

    $result = InterceptorManager::applyInterceptor(
        $interceptorFunction,
        $data,
        $nodePrimaryTable,
        $nodePrimaryDatabase
    );

    expect($result)->toEqual($data);
});
