<?php

namespace Tests\MySQLReplication\Integration;

use Doctrine\DBAL\Connection;
use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\Event\DTO\FormatDescriptionEventDTO;
use MySQLReplication\Event\DTO\MariaDbAnnotateRowsDTO;
use MySQLReplication\Event\DTO\QueryDTO;
use MySQLReplication\Event\DTO\RotateDTO;
use MySQLReplication\Event\DTO\TableMapDTO;
use MySQLReplication\MySQLReplicationFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class BaseCase extends TestCase
{
    protected ?MySQLReplicationFactory $mySQLReplicationFactory;

    protected Connection $connection;

    protected string $database = 'mysqlreplication_test';

    protected ?EventDTO $currentEvent;

    protected ConfigBuilder $configBuilder;

    private TestEventSubscribers $testEventSubscribers;

    protected function setUp(): void
    {
        $this->configBuilder = (new ConfigBuilder())
            ->withUser('COLABORADOR_CENTRAL')
            ->withHost('127.0.0.1')
            ->withPassword('COLABORADOR_CENTRAL')
            ->withPort(3306)
            ->withEventsIgnore([ConstEventType::GTID_LOG_EVENT->value]);

        $this->connect();

        if (
            $this->mySQLReplicationFactory?->getServerInfo()->versionRevision >= 8 &&
            $this->mySQLReplicationFactory?->getServerInfo()->isGeneric()
        ) {
            $this->assertInstanceOf(RotateDTO::class, $this->getEvent());
        }
        $this->assertInstanceOf(FormatDescriptionEventDTO::class, $this->getEvent());
        $this->assertInstanceOf(QueryDTO::class, $this->getEvent());
        $this->assertInstanceOf(QueryDTO::class, $this->getEvent());
    }

    protected function tearDown(): void
    {
        $this->disconnect();
    }

    public function setEvent(EventDTO $eventDTO): void
    {
        $this->currentEvent = $eventDTO;
    }

    public function connect(): void
    {
        $this->mySQLReplicationFactory = new MySQLReplicationFactory($this->configBuilder->build());
        $this->testEventSubscribers = new TestEventSubscribers($this);
        $this->mySQLReplicationFactory->registerSubscriber($this->testEventSubscribers);

        $connection = $this->mySQLReplicationFactory->getDbConnection();
        if ($connection === null) {
            throw new RuntimeException('Connection not initialized');
        }
        $this->connection = $connection;
        $this->connection->executeStatement('SET SESSION time_zone = "UTC"');
        $this->connection->executeStatement('DROP DATABASE IF EXISTS ' . $this->database);
        $this->connection->executeStatement('CREATE DATABASE ' . $this->database);
        $this->connection->executeStatement('USE ' . $this->database);
        $this->connection->executeStatement('SET SESSION sql_mode = \'\';');
    }

    protected function getEvent(): EventDTO
    {
        if ($this->mySQLReplicationFactory === null) {
            throw new RuntimeException('MySQLReplicationFactory not initialized');
        }

        $this->currentEvent = null;
        while ($this->currentEvent === null) {
            $this->mySQLReplicationFactory->consume();
        }
        /** @phpstan-ignore-next-line */
        return $this->currentEvent;
    }

    protected function disconnect(): void
    {
        if ($this->mySQLReplicationFactory === null) {
            return;
        }
        $this->mySQLReplicationFactory->unregisterSubscriber($this->testEventSubscribers);
        $this->mySQLReplicationFactory = null;
    }

    protected function checkForVersion(float $version): bool
    {
        /** @phpstan-ignore-next-line */
        return $this->mySQLReplicationFactory->getServerInfo()->versionRevision < $version;
    }

    protected function createAndInsertValue(string $createQuery, string $insertQuery): EventDTO
    {
        $this->connection->executeStatement($createQuery);
        $this->connection->executeStatement($insertQuery);

        $this->assertInstanceOf(QueryDTO::class, $this->getEvent());
        $this->assertInstanceOf(QueryDTO::class, $this->getEvent());
        $this->assertInstanceOf(MariaDbAnnotateRowsDTO::class, $this->getEvent());
        $this->assertInstanceOf(TableMapDTO::class, $this->getEvent());

        return $this->getEvent();
    }

    public function connectWithProvidedEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->mySQLReplicationFactory = new MySQLReplicationFactory(
            $this->configBuilder->build(),
            null,
            null,
            $eventDispatcher
        );

        $connection = $this->mySQLReplicationFactory->getDbConnection();
        if ($connection === null) {
            throw new RuntimeException('Connection not initialized');
        }

        $this->connection = $connection;
        $this->connection->executeStatement('SET SESSION time_zone = "UTC"');
        $this->connection->executeStatement('DROP DATABASE IF EXISTS ' . $this->database);
        $this->connection->executeStatement('CREATE DATABASE ' . $this->database);
        $this->connection->executeStatement('USE ' . $this->database);
        $this->connection->executeStatement('SET SESSION sql_mode = \'\';');

        if (
            $this->mySQLReplicationFactory->getServerInfo()->versionRevision >= 8 &&
            $this->mySQLReplicationFactory->getServerInfo()->isGeneric()
        ) {
            $this->assertInstanceOf(RotateDTO::class, $this->getEvent());
        }

        $this->assertInstanceOf(FormatDescriptionEventDTO::class, $this->getEvent());
        $this->assertInstanceOf(QueryDTO::class, $this->getEvent());
        $this->assertInstanceOf(QueryDTO::class, $this->getEvent());
    }
}
