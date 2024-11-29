<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);
use MySQLReplication\Cache\ArrayCache;
use MySQLReplication\Config\ConfigBuilder;
beforeEach(function () {
    $this->arrayCache = new ArrayCache();
});
test('should get', function () {
    $this->arrayCache->set('foo', 'bar');
    self::assertSame('bar', $this->arrayCache->get('foo'));
});
test('should set', function () {
    $this->arrayCache->set('foo', 'bar');
    self::assertSame('bar', $this->arrayCache->get('foo'));
});
test('should clear cache on set', function () {
    (new ConfigBuilder())->withTableCacheSize(1)
        ->build();

    $this->arrayCache->set('foo', 'bar');
    $this->arrayCache->set('foo', 'bar');
    self::assertSame('bar', $this->arrayCache->get('foo'));
});
test('should delete', function () {
    $this->arrayCache->set('foo', 'bar');
    $this->arrayCache->delete('foo');
    self::assertNull($this->arrayCache->get('foo'));
});
test('should clear', function () {
    $this->arrayCache->set('foo', 'bar');
    $this->arrayCache->set('foo1', 'bar1');
    $this->arrayCache->clear();
    self::assertNull($this->arrayCache->get('foo'));
});
test('should get multiple', function () {
    $expect = [
        'foo' => 'bar',
        'foo1' => 'bar1',
    ];
    $this->arrayCache->setMultiple($expect);
    self::assertSame([
        'foo' => 'bar',
    ], $this->arrayCache->getMultiple(['foo']));
});
test('should set multiple', function () {
    $expect = [
        'foo' => 'bar',
        'foo1' => 'bar1',
    ];
    $this->arrayCache->setMultiple($expect);
    self::assertSame($expect, $this->arrayCache->getMultiple(['foo', 'foo1']));
});
test('should delete multiple', function () {
    $expect = [
        'foo' => 'bar',
        'foo1' => 'bar1',
        'foo2' => 'bar2',
    ];
    $this->arrayCache->setMultiple($expect);
    $this->arrayCache->deleteMultiple(['foo', 'foo1']);
    self::assertSame([
        'foo2' => 'bar2',
    ], $this->arrayCache->getMultiple(['foo2']));
});
test('should has', function () {
    self::assertFalse($this->arrayCache->has('foo'));
    $this->arrayCache->set('foo', 'bar');
    self::assertTrue($this->arrayCache->has('foo'));
});
