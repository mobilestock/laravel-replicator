<?php

use MobileStock\LaravelReplicator\Console\Commands\StartReplicationCommand;
use MobileStock\LaravelReplicator\ReplicatorServiceProvider;

test('should register StartReplicationCommand correctly', function () {
    $provider = Mockery::mock(ReplicatorServiceProvider::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $provider
        ->shouldReceive('commands')
        ->once()
        ->with([StartReplicationCommand::class]);

    $provider->register();

    expect(true)->toBeTrue();
});

afterEach(function () {
    Mockery::close();
});
