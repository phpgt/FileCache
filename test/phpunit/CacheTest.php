<?php
namespace Gt\FileCache\Test;

use Gt\FileCache\Cache;
use Gt\FileCache\FileAccess;
use PHPUnit\Framework\TestCase;
use stdClass;

class CacheTest extends TestCase {
	public function tearDown():void {
		exec("rm -rf " . sys_get_temp_dir() . "/phpgt-filecache");
	}

	public function testGet_expectedValueReturned():void {
		$sut = $this->getSut();
		$value = "test-value";
		$result = $sut->get("test", fn() => $value);
		self::assertSame($value, $result);
	}

	public function testGet_expectedValueReturnedOnMultipleCalls():void {
		$sut = $this->getSut();
		$value = "test-value";
		$result = $sut->get("test", fn() => $value);
		self::assertSame($value, $result);
		$result = $sut->get("test", fn() => $value);
		self::assertSame($value, $result);
	}

	public function testGet_multipleCallsDoesNotCallbackMultipleTimes():void {
		$sut = $this->getSut();
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

	public function testGetString():void {
		$value = uniqid();
		$sut = $this->getSut([
			"test" => $value,
		]);
		self::assertSame($value, $sut->getString("test", fn() => "new"));
	}

	public function testGetInt():void {
		$value = (string)rand(100,999);
		$sut = $this->getSut([
			"test" => $value,
		]);
		self::assertSame((int)$value, $sut->getInt("test", fn() => 2));
	}

	public function testGetFoat():void {
		$value = (string)(rand(100,999) * 3.14159);
		$sut = $this->getSut([
			"test" => $value,
		]);
		self::assertSame((float)$value, $sut->getFloat("test", fn() => 3.141));
	}

	public function testGetBool():void {
		$value = "yes";
		$sut = $this->getSut([
			"test" => $value,
		]);
		self::assertTrue($sut->getBool("test", fn() => false));
	}

	public function testGetDateTime():void {
		$value = "1988-04-05";
		$sut = $this->getSut([
			"test" => $value,
		]);
		self::assertSame($value, $sut->getDateTime("test", fn() => 123)->format("Y-m-d"));
	}

	public function testGetClass():void {
		$value = new StdClass();
		$value->name = uniqid();
		$sut = $this->getSut([
			"test" => serialize($value),
		]);
		$class = $sut->getClass(StdClass::class, "test", fn() => false);
		self::assertSame($value->name, $class->name);
	}

	private function getSut(array $mockFiles = []):Cache {
		$mockFileAccess = null;
		if(!empty($mockFiles)) {
			$mockFileAccess = self::createMock(FileAccess::class);
			foreach($mockFiles as $key => $value) {
				$mockFileAccess->method("getData")
					->with($key)->willReturn($value);
			}
		}
		return new Cache(
			sys_get_temp_dir() . "/phpgt-filecache",
			$mockFileAccess,
		);
	}
}
