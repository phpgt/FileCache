<?php
namespace Gt\FileCache\Test;

use Gt\FileCache\Cache;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase {
	public function testGet_expectedValueReturned():void {
		$sut = new Cache(sys_get_temp_dir() . "/phpgt-filecache");
		$value = "test-value";
		$result = $sut->get("test", fn() => $value);
		self::assertSame($value, $result);
	}

	public function testGet_expectedValueReturnedOnMultipleCalls():void {
		$sut = new Cache(sys_get_temp_dir() . "/phpgt-filecache");
		$value = "test-value";
		$result = $sut->get("test", fn() => $value);
		self::assertSame($value, $result);
		$result = $sut->get("test", fn() => $value);
		self::assertSame($value, $result);
	}

	public function testGet_multipleCallsDoesNotCallbackMultipleTimes():void {
		$sut = new Cache(sys_get_temp_dir() . "/phpgt/filecache");
		$name = uniqid("test-");
		$value = "test-value-123";
		$count = 0;

		$callback = function()use($value, &$count):string {
			$count++;
			return $value;
		};
		$result = $sut->get($name, $callback);
		self::assertSame($value, $result);
		self::assertSame(1, $count);

		$result = $sut->get($name, $callback);
		self::assertSame($value, $result);
		self::assertSame(1, $count);
	}
}
