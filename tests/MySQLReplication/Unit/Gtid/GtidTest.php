<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);
use MySQLReplication\Gtid\Gtid;
use MySQLReplication\Gtid\GtidException;
test('should get encoded', function () {
    self::assertSame(
        '9b1c8d182a7611e5a26b000c2976f3f301000000000000000100000000000000b8b5020000000000',
        bin2hex(getGtid('9b1c8d18-2a76-11e5-a26b-000c2976f3f3:1-177592')->getEncoded())
    );
    self::assertSame(
        '9b1c8d182a7611e5a26b000c2976f3f3010000000000000001000000000000000200000000000000',
        bin2hex(getGtid('9b1c8d18-2a76-11e5-a26b-000c2976f3f3:1')->getEncoded())
    );
});
test('should get encoded length', function () {
    self::assertSame(40, getGtid('9b1c8d18-2a76-11e5-a26b-000c2976f3f3:1-177592')->getEncodedLength());
});
test('should throw error on incrrect gtid', function () {
    $this->expectException(GtidException::class);
    $this->expectExceptionMessage(GtidException::INCORRECT_GTID_MESSAGE);
    $this->expectExceptionCode(GtidException::INCORRECT_GTID_CODE);

    getGtid('not gtid');
});
function getGtid(string $data): Gtid
{
    return new Gtid($data);
}
