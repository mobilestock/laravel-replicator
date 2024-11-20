<?php

namespace Tests\Replicator;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use MobileStock\LaravelReplicator\ReplicatorServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $app = new Container();
        Container::setInstance($app);
        Facade::setFacadeApplication($app);
    }

    protected function getPackageProviders($app): array
    {
        return [ReplicatorServiceProvider::class];
    }
}
