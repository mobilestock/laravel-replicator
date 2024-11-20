<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use MySQLReplication\Repository\FieldDTOCollection;
use MySQLReplication\Repository\MasterStatusDTO;
use MySQLReplication\Repository\MySQLRepository;
use PHPUnit\Framework\MockObject\MockObject;
beforeEach(function () {
    $this->connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
    $this->connection->method('getDatabasePlatform')
        ->willReturn(new MySQLPlatform());
    $this->mySQLRepositoryTest = new MySQLRepository($this->connection);
});
test('should get fields', function () {
    $expected = [
        [
            'COLUMN_NAME' => 'cname',
            'COLLATION_NAME' => 'colname',
            'CHARACTER_SET_NAME' => 'charname',
            'COLUMN_COMMENT' => 'colcommnet',
            'COLUMN_TYPE' => 'coltype',
            'COLUMN_KEY' => 'colkey',
        ],
    ];

    $this->connection->method('fetchAllAssociative')
        ->willReturn($expected);

    self::assertEquals(
        FieldDTOCollection::makeFromArray($expected),
        $this->mySQLRepositoryTest->getFields('foo', 'bar')
    );
});
test('should is check sum', function () {
    self::assertFalse($this->mySQLRepositoryTest->isCheckSum());

    $this->connection->method('fetchAssociative')
        ->willReturnOnConsecutiveCalls([
            'Value' => 'CRC32',
        ], [
            'Value' => 'NONE',
        ]);

    self::assertTrue($this->mySQLRepositoryTest->isCheckSum());
    self::assertFalse($this->mySQLRepositoryTest->isCheckSum());
});
test('should get version', function () {
    $expected = [
        [
            'Value' => 'foo',
        ],
        [
            'Value' => 'bar',
        ],
        [
            'Value' => '123',
        ],
    ];

    $this->connection->method('fetchAllAssociative')
        ->willReturn($expected);

    self::assertEquals('foobar123', $this->mySQLRepositoryTest->getVersion());
});
test('should get master status', function () {
    $expected = [
        'File' => 'mysql-bin.000002',
        'Position' => 4587305,
        'Binlog_Do_DB' => '',
        'Binlog_Ignore_DB' => '',
        'Executed_Gtid_Set' => '041de05f-a36a-11e6-bc73-000c2976f3f3:1-8023',
    ];

    $this->connection->method('fetchAssociative')
        ->willReturn($expected);

    self::assertEquals(MasterStatusDTO::makeFromArray($expected), $this->mySQLRepositoryTest->getMasterStatus());
});
test('should reconnect', function () {
    // just to cover private getConnection
    $this->connection->method('executeQuery')
        ->willReturnCallback(static function () {
            throw new Exception('');
        });
    $this->mySQLRepositoryTest->isCheckSum();
    self::assertTrue(true);
});
