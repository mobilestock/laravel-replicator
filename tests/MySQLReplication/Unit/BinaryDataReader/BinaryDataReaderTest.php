<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);
use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\BinaryDataReader\BinaryDataReaderException;
use PHPUnit\Framework\Attributes\DataProvider;
test('should read', function () {
    $expected = 'zażółć gęślą jaźń';
    self::assertSame($expected, pack('H*', getBinaryRead(unpack('H*', $expected)[1])->read(52)));
});
test('should read coded binary', function () {
    self::assertSame(0, getBinaryRead(pack('C', ''))->readCodedBinary());
    self::assertNull(getBinaryRead(pack('C', BinaryDataReader::NULL_COLUMN))->readCodedBinary());
    self::assertSame(
        0,
        getBinaryRead(pack('i', BinaryDataReader::UNSIGNED_SHORT_COLUMN))->readCodedBinary()
    );
    self::assertSame(
        0,
        getBinaryRead(pack('i', BinaryDataReader::UNSIGNED_INT24_COLUMN))->readCodedBinary()
    );
});
test('should throw error on unknown coded binary', function () {
    $this->expectException(BinaryDataReaderException::class);

    getBinaryRead(pack('i', 255))
        ->readCodedBinary();
});
dataset('dataProviderForUInt', function () {
    return [
        [1, pack('c', 1), 1],
        [2, pack('v', 9999), 9999],
        [3, pack('CCC', 160, 190, 15), 1031840],
        [4, pack('I', 123123543), 123123543],
        [5, pack('CI', 71, 2570258120), 657986078791],
        [6, pack('v3', 2570258120, 2570258120, 2570258120), 7456176998088],
        [7, pack('CSI', 66, 7890, 2570258120), 43121775657013826],
    ];
});
test('should read read uint64', function () {
    expect(getBinaryRead(pack('VV', 4278190080, 4278190080))
        ->readUInt64())->toBe('18374686483949813760');
});
test('should read uint by size', function (mixed $size, mixed $data, mixed $expected) {
    self::assertSame($expected, getBinaryRead($data)->readUIntBySize($size));
})->with('dataProviderForUInt');
test('should throw error on read uint by size not supported', function () {
    $this->expectException(BinaryDataReaderException::class);

    getBinaryRead('')
        ->readUIntBySize(32);
});
dataset('dataProviderForBeInt', function () {
    return [
        [1, pack('c', 4), 4],
        [2, pack('n', 9999), 9999],
        [3, pack('CCC', 160, 190, 15), -6242801],
        [4, pack('i', 123123543), 1471632903],
        [5, pack('NC', 71, 2570258120), 18376],
    ];
});
test('should read int be by size', function (int $size, string $data, int $expected) {
    self::assertSame($expected, getBinaryRead($data)->readIntBeBySize($size));
})->with('dataProviderForBeInt');
test('should throw error on read int be by size not supported', function () {
    $this->expectException(BinaryDataReaderException::class);

    getBinaryRead('')
        ->readIntBeBySize(666);
});
test('should read int16', function () {
    $expected = 1000;
    self::assertSame($expected, getBinaryRead(pack('s', $expected))->readInt16());
});
test('should unread advance', function () {
    $binaryDataReader = getBinaryRead('123');

    self::assertEquals('123', $binaryDataReader->getBinaryData());
    self::assertEquals(0, $binaryDataReader->getReadBytes());

    $binaryDataReader->advance(2);

    self::assertEquals('3', $binaryDataReader->getBinaryData());
    self::assertEquals(2, $binaryDataReader->getReadBytes());

    $binaryDataReader->unread('12');

    self::assertEquals('123', $binaryDataReader->getBinaryData());
    self::assertEquals(0, $binaryDataReader->getReadBytes());
});
test('should read int24', function () {
    self::assertSame(-6513508, getBinaryRead(pack('C3', -100, -100, -100))->readInt24());
});
test('should read int64', function () {
    self::assertSame('-72057589759737856', getBinaryRead(pack('VV', 4278190080, 4278190080))->readInt64());
});
test('should read length coded pascal string', function () {
    $expected = 255;
    self::assertSame(
        $expected,
        hexdec(bin2hex(getBinaryRead(pack('cc', 1, $expected))->readLengthString(1)))
    );
});
test('should read int32', function () {
    $expected = 777333;
    self::assertSame($expected, getBinaryRead(pack('i', $expected))->readInt32());
});
test('should read float', function () {
    $expected = 0.001;

    // we need to add round as php have problem with precision in floats
    self::assertSame($expected, round(getBinaryRead(pack('f', $expected))->readFloat(), 3));
});
test('should read double', function () {
    $expected = 1321312312.143567586;
    self::assertSame($expected, getBinaryRead(pack('d', $expected))->readDouble());
});
test('should read table id', function () {
    self::assertSame(
        '7456176998088',
        getBinaryRead(pack('v3', 2570258120, 2570258120, 2570258120))
            ->readTableId()
    );
});
test('should check is completed', function () {
    self::assertFalse(getBinaryRead('')->isComplete(1));

    $r = getBinaryRead(str_repeat('-', 30));
    $r->advance(21);
    self::assertTrue($r->isComplete(1));
});
test('should pack64bit', function () {
    $expected = 9223372036854775807;
    self::assertSame((string)$expected, getBinaryRead(BinaryDataReader::pack64bit($expected))->readInt64());
});
test('should get binary data length', function () {
    self::assertSame(3, getBinaryRead('foo')->getBinaryDataLength());
});
function getBinaryRead(string $data): BinaryDataReader
{
    return new BinaryDataReader($data);
}
