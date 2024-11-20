<?php

/** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection PhpPossiblePolymorphicInvocationInspection */

declare(strict_types=1);

use Tests\MySQLReplication\Integration\BaseCase;

uses(BaseCase::class);

test('Should be decimal', function () {
    $create_query = 'CREATE TABLE test (test DECIMAL(2,1))';
    $insert_query = 'INSERT INTO test VALUES(4.2)';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toEqual(4.2);
});

test('Should be decimal long values', function () {
    $create_query = 'CREATE TABLE test (test DECIMAL(20,10))';
    $insert_query = 'INSERT INTO test VALUES(9000000123.123456)';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    $expect = '9000000123.1234560000';
    $value = $event->values[0]['test'];
    expect($value)->toBe($expect);
    expect(mb_strlen($value))->toEqual(mb_strlen($expect));
});

test('Should be decimal long values 2', function () {
    $create_query = 'CREATE TABLE test (test DECIMAL(20,10))';
    $insert_query = 'INSERT INTO test VALUES(9000000123.0000012345)';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toEqual('9000000123.0000012345');
});

test('Should be decimal negative values', function () {
    $create_query = 'CREATE TABLE test (test DECIMAL(20,10), test2 DECIMAL(11,4), test3 DECIMAL(40,30))';
    $insert_query = 'INSERT INTO test VALUES(-42000.123456, -51.1234, -51.123456789098765432123456789)';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toEqual('-42000.1234560000');
    expect($event->values[0]['test2'])->toEqual('-51.1234');
    expect($event->values[0]['test3'])->toEqual('-51.123456789098765432123456789000');
});

test('Should be decimal two values', function () {
    $create_query = 'CREATE TABLE test ( test DECIMAL(2,1), test2 DECIMAL(20,10) )';
    $insert_query = 'INSERT INTO test VALUES(4.2, 42000.123456)';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toEqual('4.2');
    expect($event->values[0]['test2'])->toEqual('42000.1234560000');
});

test('Should be decimal zero scale 1', function () {
    $create_query = 'CREATE TABLE test (test DECIMAL(23,0))';
    $insert_query = 'INSERT INTO test VALUES(10)';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toEqual('10');
});

test('Should be decimal zero scale 2', function () {
    $create_query = 'CREATE TABLE test (test DECIMAL(23,0))';
    $insert_query = 'INSERT INTO test VALUES(12345678912345678912345)';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toEqual('12345678912345678912345');
});

test('Should be decimal zero scale 3', function () {
    $create_query = 'CREATE TABLE test (test DECIMAL(23,0))';
    $insert_query = 'INSERT INTO test VALUES(100000.0)';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toEqual('100000');
});

test('Should be decimal zero scale 4', function () {
    $create_query = 'CREATE TABLE test (test DECIMAL(23,0))';
    $insert_query = 'INSERT INTO test VALUES(-100000.0)';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toEqual('-100000');
});

test('Should be decimal zero scale 5', function () {
    $create_query = 'CREATE TABLE test (test DECIMAL(23,0))';
    $insert_query = 'INSERT INTO test VALUES(-1234567891234567891234)';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toEqual('-1234567891234567891234');
});

test('Should be tiny int', function () {
    $create_query = 'CREATE TABLE test (id TINYINT UNSIGNED NOT NULL, test TINYINT)';
    $insert_query = 'INSERT INTO test VALUES(255, -128)';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['id'])->toEqual(255);
    expect($event->values[0]['test'])->toEqual(-128);
});

test('Should be maps to boolean true', function () {
    $create_query = 'CREATE TABLE test (id TINYINT UNSIGNED NOT NULL, test BOOLEAN)';
    $insert_query = 'INSERT INTO test VALUES(1, TRUE)';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['id'])->toEqual(1);
    expect($event->values[0]['test'])->toEqual(1);
});

test('Should be maps to boolean false', function () {
    $create_query = 'CREATE TABLE test (id TINYINT UNSIGNED NOT NULL, test BOOLEAN)';
    $insert_query = 'INSERT INTO test VALUES(1, FALSE)';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['id'])->toEqual(1);
    expect($event->values[0]['test'])->toEqual(0);
});

test('Should be maps to none', function () {
    $create_query = 'CREATE TABLE test (id TINYINT UNSIGNED NOT NULL, test BOOLEAN)';
    $insert_query = 'INSERT INTO test VALUES(1, NULL)';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['id'])->toEqual(1);
    expect($event->values[0]['test'])->toBeNull();
});

test('Should be maps to short', function () {
    $create_query = 'CREATE TABLE test (id SMALLINT UNSIGNED NOT NULL, test SMALLINT)';
    $insert_query = 'INSERT INTO test VALUES(65535, -32768)';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['id'])->toEqual(65535);
    expect($event->values[0]['test'])->toEqual(-32768);
});

test('Should be long', function () {
    $create_query = 'CREATE TABLE test (id INT UNSIGNED NOT NULL, test INT)';
    $insert_query = 'INSERT INTO test VALUES(4294967295, -2147483648)';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['id'])->toEqual(4294967295);
    expect($event->values[0]['test'])->toEqual(-2147483648);
});

test('Should be float', function () {
    $create_query = 'CREATE TABLE test (id FLOAT NOT NULL, test FLOAT)';
    $insert_query = 'INSERT INTO test VALUES(42.42, -84.84)';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['id'])->toEqual(42.42);
    expect($event->values[0]['test'])->toEqual(-84.84);
});

test('Should be double', function () {
    $create_query = 'CREATE TABLE test (id DOUBLE NOT NULL, test DOUBLE)';
    $insert_query = 'INSERT INTO test VALUES(42.42, -84.84)';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['id'])->toEqual(42.42);
    expect($event->values[0]['test'])->toEqual(-84.84);
});

test('Should be timestamp', function () {
    $create_query = 'CREATE TABLE test (test TIMESTAMP);';
    $insert_query = 'INSERT INTO test VALUES("1984-12-03 12:33:07")';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toEqual('1984-12-03 12:33:07');
});

test('Should be timestamp MySQL 5.6', function () {
    /*
     * https://mariadb.com/kb/en/library/microseconds-in-mariadb/
     * MySQL 5.6 introduced microseconds using a slightly different implementation to MariaDB 5.3.
     * Since MariaDB 10.1, MariaDB has defaulted to the MySQL format ...
     */
    if ($this->mySQLReplicationFactory?->getServerInfo()->isMariaDb() && $this->checkForVersion(10.1)) {
        $this->markTestIncomplete('Only for mariadb 10.1 or higher');
    } elseif ($this->checkForVersion(5.6)) {
        $this->markTestIncomplete('Only for mysql 5.6 or higher');
    }

    $create_query = 'CREATE TABLE test (test0 TIMESTAMP(0),
        test1 TIMESTAMP(1),
        test2 TIMESTAMP(2),
        test3 TIMESTAMP(3),
        test4 TIMESTAMP(4),
        test5 TIMESTAMP(5),
        test6 TIMESTAMP(6));';
    $insert_query = 'INSERT INTO test VALUES(
        "1984-12-03 12:33:07",
        "1984-12-03 12:33:07.1",
        "1984-12-03 12:33:07.12",
        "1984-12-03 12:33:07.123",
        "1984-12-03 12:33:07.1234",
        "1984-12-03 12:33:07.12345",
        "1984-12-03 12:33:07.123456")';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test0'])->toEqual('1984-12-03 12:33:07');
    expect($event->values[0]['test1'])->toEqual('1984-12-03 12:33:07.100000');
    expect($event->values[0]['test2'])->toEqual('1984-12-03 12:33:07.120000');
    expect($event->values[0]['test3'])->toEqual('1984-12-03 12:33:07.123000');
    expect($event->values[0]['test4'])->toEqual('1984-12-03 12:33:07.123400');
    expect($event->values[0]['test5'])->toEqual('1984-12-03 12:33:07.123450');
    expect($event->values[0]['test6'])->toEqual('1984-12-03 12:33:07.123456');
});

test('Should be long long', function () {
    $create_query = 'CREATE TABLE test (id BIGINT UNSIGNED NOT NULL, test BIGINT)';
    $insert_query = 'INSERT INTO test VALUES(18446744073709551615, -9223372036854775808)';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['id'])->toEqual('18446744073709551615');
    expect($event->values[0]['test'])->toEqual('-9223372036854775808');
});

test('Should be int24', function () {
    $create_query =
        'CREATE TABLE test (id MEDIUMINT UNSIGNED NOT NULL, test MEDIUMINT, test2 MEDIUMINT, test3 MEDIUMINT, test4 MEDIUMINT, test5 MEDIUMINT)';
    $insert_query = 'INSERT INTO test VALUES(16777215, 8388607, -8388608, 8, -8, 0)';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['id'])->toEqual(16777215);
    expect($event->values[0]['test'])->toEqual(8388607);
    expect($event->values[0]['test2'])->toEqual(-8388608);
    expect($event->values[0]['test3'])->toEqual(8);
    expect($event->values[0]['test4'])->toEqual(-8);
    expect($event->values[0]['test5'])->toEqual(0);
});

test('Should be date', function () {
    $create_query = 'CREATE TABLE test (test DATE);';
    $insert_query = 'INSERT INTO test VALUES("1984-12-03")';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toEqual('1984-12-03');
});

test('Should be zero date', function () {
    $create_query = 'CREATE TABLE test (id INTEGER, test DATE, test2 DATE);';
    $insert_query = 'INSERT INTO test (id, test2) VALUES(1, "0000-01-21")';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toBeNull();
    expect($event->values[0]['test2'])->toBeNull();
});

test('Should be zero month', function () {
    $create_query = 'CREATE TABLE test (id INTEGER, test DATE, test2 DATE);';
    $insert_query = 'INSERT INTO test (id, test2) VALUES(1, "2015-00-21")';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toBeNull();
    expect($event->values[0]['test2'])->toBeNull();
});

test('Should be zero day', function () {
    $create_query = 'CREATE TABLE test (id INTEGER, test DATE, test2 DATE);';
    $insert_query = 'INSERT INTO test (id, test2) VALUES(1, "2015-05-00")';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toBeNull();
    expect($event->values[0]['test2'])->toBeNull();
});

test('Should be time', function () {
    $create_query = 'CREATE TABLE test (test TIME);';
    $insert_query = 'INSERT INTO test VALUES("12:33:18")';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toEqual('12:33:18');
});

test('Should be zero time', function () {
    $create_query = 'CREATE TABLE test (id INTEGER, test TIME NOT NULL DEFAULT 0);';
    $insert_query = 'INSERT INTO test (id) VALUES(1)';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toEqual('00:00:00');
});

test('Should be datetime', function () {
    $create_query = 'CREATE TABLE test (test DATETIME);';
    $insert_query = 'INSERT INTO test VALUES("1984-12-03 12:33:07")';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toEqual('1984-12-03 12:33:07');
});

test('Should be zero datetime', function () {
    $create_query = 'CREATE TABLE test (id INTEGER, test DATETIME NOT NULL DEFAULT 0);';
    $insert_query = 'INSERT INTO test (id) VALUES(1)';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toBeNull();
});

test('Should be broken datetime', function () {
    $create_query = 'CREATE TABLE test (test DATETIME NOT NULL);';
    $insert_query = 'INSERT INTO test VALUES("2013-00-00 00:00:00")';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toBeNull();
});

test('Should return null on zero date datetime', function () {
    $create_query = 'CREATE TABLE test (test DATETIME NOT NULL);';
    $insert_query = 'INSERT INTO test VALUES("0000-00-00 00:00:00")';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toBeNull();
});

test('Should be year', function () {
    $create_query = 'CREATE TABLE test (test YEAR(4), test2 YEAR, test3 YEAR)';
    $insert_query = 'INSERT INTO test VALUES(1984, 1984, 0000)';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toEqual(1984);
    expect($event->values[0]['test2'])->toEqual(1984);
    expect($event->values[0]['test3'])->toBeNull();
});

test('Should be varchar', function () {
    $create_query = 'CREATE TABLE test (test VARCHAR(242)) CHARACTER SET latin1 COLLATE latin1_bin;';
    $insert_query = 'INSERT INTO test VALUES("Hello")';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toEqual('Hello');
});

test('Should be 1024 chars long varchar', function () {
    $expected = str_repeat('-', 1024);

    $create_query = 'CREATE TABLE test (test VARCHAR(1024)) CHARACTER SET latin1 COLLATE latin1_bin;';
    $insert_query = 'INSERT INTO test VALUES("' . $expected . '")';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toEqual($expected);
});

test('Should be bit', function () {
    $create_query = 'CREATE TABLE test (
        test BIT(6),
        test2 BIT(16),
        test3 BIT(12),
        test4 BIT(9),
        test5 BIT(64)
     );';
    $insert_query = "INSERT INTO test VALUES(
        b'100010',
        b'1000101010111000',
        b'100010101101',
        b'101100111',
        b'1101011010110100100111100011010100010100101110111011101011011010'
    )";

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toEqual('100010');
    expect($event->values[0]['test2'])->toEqual('1000101010111000');
    expect($event->values[0]['test3'])->toEqual('100010101101');
    expect($event->values[0]['test4'])->toEqual('101100111');
    expect($event->values[0]['test5'])->toEqual('1101011010110100100111100011010100010100101110111011101011011010');
});

test('Should be enum', function () {
    $create_query = 'CREATE TABLE test
        (
            test ENUM("a", "ba", "c"),
            test2 ENUM("a", "ba", "c"),
            test3 ENUM("foo", "bar")
        )
        CHARACTER SET latin1 COLLATE latin1_bin;';
    $insert_query = 'INSERT INTO test VALUES("ba", "a", "not_exists")';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toEqual('ba');
    expect($event->values[0]['test2'])->toEqual('a');
    expect($event->values[0]['test3'])->toEqual('');
});

test('Should be set', function () {
    $create_query =
        'CREATE TABLE test (test SET("a", "ba", "c"), test2 SET("a", "ba", "c")) CHARACTER SET latin1 COLLATE latin1_bin;';
    $insert_query = 'INSERT INTO test VALUES("ba,a,c", "a,c")';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toEqual(['a', 'ba', 'c']);
    expect($event->values[0]['test2'])->toEqual(['a', 'c']);
});

test('Should be tiny blob', function () {
    $create_query = 'CREATE TABLE test (test TINYBLOB, test2 TINYTEXT) CHARACTER SET latin1 COLLATE latin1_bin;';
    $insert_query = 'INSERT INTO test VALUES("Hello", "World")';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toEqual('Hello');
    expect($event->values[0]['test2'])->toEqual('World');
});

test('Should be medium blob', function () {
    $create_query = 'CREATE TABLE test (test MEDIUMBLOB, test2 MEDIUMTEXT) CHARACTER SET latin1 COLLATE latin1_bin;';
    $insert_query = 'INSERT INTO test VALUES("Hello", "World")';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toEqual('Hello');
    expect($event->values[0]['test2'])->toEqual('World');
});

test('Should be null on boolean type', function () {
    $create_query = 'CREATE TABLE test (test BOOLEAN);';
    $insert_query = 'INSERT INTO test VALUES(NULL)';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toBeNull();
});

test('Should be long blob', function () {
    $create_query = 'CREATE TABLE test (test LONGBLOB, test2 LONGTEXT) CHARACTER SET latin1 COLLATE latin1_bin;';
    $insert_query = 'INSERT INTO test VALUES("Hello", "World")';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toEqual('Hello');
    expect($event->values[0]['test2'])->toEqual('World');
});

test('Should be longer text than 16Mb', function () {
    // https://dev.mysql.com/doc/internals/en/mysql-packet.html
    // https://dev.mysql.com/doc/internals/en/sending-more-than-16mbyte.html

    $long_text_data = '';
    for ($i = 0; $i < 40000000; ++$i) {
        $long_text_data .= 'a';
    }
    $create_query = 'CREATE TABLE test (data LONGTEXT);';
    $insert_query = 'INSERT INTO test (data) VALUES ("' . $long_text_data . '")';
    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect(mb_strlen($event->values[0]['data']))->toEqual(mb_strlen($long_text_data));

    $long_text_data = null;
});

test('Should be blob', function () {
    $create_query = 'CREATE TABLE test (test BLOB, test2 TEXT) CHARACTER SET latin1 COLLATE latin1_bin;';
    $insert_query = 'INSERT INTO test VALUES("Hello", "World")';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toEqual('Hello');
    expect($event->values[0]['test2'])->toEqual('World');
});

test('Should be string', function () {
    $create_query = 'CREATE TABLE test (test CHAR(12)) CHARACTER SET latin1 COLLATE latin1_bin;';
    $insert_query = 'INSERT INTO test VALUES("Hello")';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toEqual('Hello');
});

test('Should be geometry', function () {
    $prefix = 'ST_';
    if ($this->checkForVersion(5.6)) {
        $prefix = '';
    }

    $create_query = 'CREATE TABLE test (test GEOMETRY);';
    $insert_query = 'INSERT INTO test VALUES(' . $prefix . 'GeomFromText("POINT(1 1)"))';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect(bin2hex($event->values[0]['test']))->toEqual('000000000101000000000000000000f03f000000000000f03f');
});

test('Should be null', function () {
    $create_query = 'CREATE TABLE test (
        test TINYINT NULL DEFAULT NULL,
        test2 TINYINT NULL DEFAULT NULL,
        test3 TINYINT NULL DEFAULT NULL,
        test4 TINYINT NULL DEFAULT NULL,
        test5 TINYINT NULL DEFAULT NULL,
        test6 TINYINT NULL DEFAULT NULL,
        test7 TINYINT NULL DEFAULT NULL,
        test8 TINYINT NULL DEFAULT NULL,
        test9 TINYINT NULL DEFAULT NULL,
        test10 TINYINT NULL DEFAULT NULL,
        test11 TINYINT NULL DEFAULT NULL,
        test12 TINYINT NULL DEFAULT NULL,
        test13 TINYINT NULL DEFAULT NULL,
        test14 TINYINT NULL DEFAULT NULL,
        test15 TINYINT NULL DEFAULT NULL,
        test16 TINYINT NULL DEFAULT NULL,
        test17 TINYINT NULL DEFAULT NULL,
        test18 TINYINT NULL DEFAULT NULL,
        test19 TINYINT NULL DEFAULT NULL,
        test20 TINYINT NULL DEFAULT NULL
        )';
    $insert_query = 'INSERT INTO test (test, test2, test3, test7, test20) VALUES(NULL, -128, NULL, 42, 84)';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toBeNull();
    expect($event->values[0]['test2'])->toEqual(-128);
    expect($event->values[0]['test3'])->toBeNull();
    expect($event->values[0]['test7'])->toEqual(42);
    expect($event->values[0]['test20'])->toEqual(84);
});

test('Should be encoded latin1', function () {
    $this->connection->executeStatement('SET CHARSET latin1');

    $string = "\00e9";

    $create_query = 'CREATE TABLE test (test CHAR(12)) CHARACTER SET latin1 COLLATE latin1_bin;';
    $insert_query = 'INSERT INTO test VALUES("' . $string . '");';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toEqual($string);
});

test('Should be encoded UTF8', function () {
    $this->connection->executeStatement('SET CHARSET utf8');

    $string = "\20ac";

    $create_query = 'CREATE TABLE test (test CHAR(12)) CHARACTER SET utf8 COLLATE utf8_bin;';
    $insert_query = 'INSERT INTO test VALUES("' . $string . '");';

    $event = $this->createAndInsertValue($create_query, $insert_query);

    expect($event->values[0]['test'])->toEqual($string);
});

test('Should be json', function () {
    if ($this->checkForVersion(5.7)) {
        $this->markTestIncomplete('Only for mysql 5.7 or higher');
    }

    $create_query = 'CREATE TABLE t1 (i INT, j JSON)';
    $insert_query = "INSERT INTO t1 VALUES 
        (0, NULL), 
        (1, '{\"a\": 2}'),
        (2, '[1,2]'),
        (3, '{\"a\":\"b\", \"c\":\"d\",\"ab\":\"abc\", \"bc\": [\"x\", \"y\"]}'),
        (4, '[\"here\", [\"I\", \"am\"], \"!!!\"]'),
        (5, '\"scalar string\"'),
        (6, 'true'),
        (7, 'false'),
        (8, 'null'),
        (9, '-1'),
        (10, '1'),
        (11, '32767'),
        (12, '32768'),
        (13, '-32768'),
        (14, '-32769'),
        (15, '2147483647'),
        (16, '2147483648'),
        (17, '-2147483648'),
        (18, '-2147483649'),
        (19, '18446744073709551615'),
        (20, '18446744073709551616'),
        (21, '3.14'),
        (22, '{}'),
        (23, '[]'),
        (24, '[]'),
        (25, '{\"bool\": true}'),
        (26, '{\"bool\": false}'),
        (27, '{\"null\": null}'),
        (28, '[\"\\\\\"test\"]')
    ";

    $event = $this->createAndInsertValue($create_query, $insert_query);

    $results = $event->values;

    expect($results[0]['j'])->toBeNull();
    expect($results[1]['j'])->toEqual('{"a": 2}');
    expect($results[2]['j'])->toEqual('[1,2]');
    expect($results[3]['j'])->toEqual('{"a":"b", "c":"d","ab":"abc", "bc": ["x", "y"]}');
    expect($results[4]['j'])->toEqual('["here", ["I", "am"], "!!!"]');
    expect($results[5]['j'])->toEqual('"scalar string"');
    expect($results[6]['j'])->toEqual('true');
    expect($results[7]['j'])->toEqual('false');
    expect($results[8]['j'])->toEqual('null');
    expect($results[9]['j'])->toEqual('-1');
    expect($results[10]['j'])->toEqual('1');
    expect($results[11]['j'])->toEqual('32767');
    expect($results[12]['j'])->toEqual('32768');
    expect($results[13]['j'])->toEqual('-32768');
    expect($results[14]['j'])->toEqual('-32769');
    expect($results[15]['j'])->toEqual('2147483647');
    expect($results[16]['j'])->toEqual('2147483648');
    expect($results[17]['j'])->toEqual('-2147483648');
    expect($results[18]['j'])->toEqual('-2147483649');
    expect($results[19]['j'])->toEqual('18446744073709551615');
    expect($results[20]['j'])->toEqual('18446744073709551616');
    expect($results[21]['j'])->toEqual('3.14');
    expect($results[22]['j'])->toEqual('{}');
    expect($results[23]['j'])->toEqual('[]');
    expect($results[24]['j'])->toEqual('[]');
    expect($results[25]['j'])->toEqual('{"bool": true}');
    expect($results[26]['j'])->toEqual('{"bool": false}');
    expect($results[27]['j'])->toEqual('{"null": null}');
    expect($results[28]['j'])->toEqual('["\"test"]');
});
