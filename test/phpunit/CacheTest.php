<?php
namespace Gt\FileCache\Test;

use Gt\FileCache\Cache;
use Gt\FileCache\FileAccess;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use SplFixedArray;
use stdClass;
use TypeError;

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

	public function testGetInstance():void {
		$value = new StdClass();
		$value->name = uniqid();

		$sut = $this->getSut([
			"test" => $value,
		]);
		$class = $sut->getInstance("test", StdClass::class, fn() => false);
		self::assertSame($value->name, $class->name);
	}

	public function testGetInstance_error():void {
		$value = new StdClass();
		$value->name = uniqid();

		$sut = $this->getSut([
			"test" => $value,
		]);
		self::expectException(TypeError::class);
		self::expectExceptionMessage("Value is not an instance of SplFileInfo");
		$sut->getInstance("test", SplFileInfo::class, fn() => false);
	}

	public function testGetArray():void {
		$value = [1, 2, 3];
		$sut = $this->getSut([
			"numbers" => $value,
		]);
		self::assertSame($value, $sut->getArray("numbers", fn() => []));
	}

	public function testGetArray_notArray():void {
		$value = (object)[1, 2, 3];
		$sut = $this->getSut([
			"numbers" => $value,
		]);
		self::expectException(TypeError::class);
		self::expectExceptionMessage("Data 'numbers' is not an array");
		$sut->getArray("numbers", fn() => []);
	}

	public function testGetTypedArray_notArray():void {
		$value = (object)[1, 2, 3];
		$sut = $this->getSut([
			"numbers" => $value,
		]);
		self::expectException(TypeError::class);
		self::expectExceptionMessage("Data 'numbers' is not an array");
		$sut->getTypedArray("numbers", "int", fn() => []);
	}

	public function testGetTypedArray_int():void {
		$value = [1, "2", 3.000];
		$sut = $this->getSut([
			"numbers" => $value,
		]);
		$typedArray = $sut->getTypedArray("numbers", "int", fn() => []);
		foreach($typedArray as $value) {
			self::assertIsInt($value);
		}
	}

	public function testGetTypedArray_intFailure():void {
		$value = [1, "2", 3.000, "four"];
		$sut = $this->getSut([
			"numbers" => $value,
		]);
		self::expectException(TypeError::class);
		$sut->getTypedArray("numbers", "int", fn() => []);
	}

	public function testGetTypedArray_float():void {
		$value = [1, "2", 3.000];
		$sut = $this->getSut([
			"numbers" => $value,
		]);
		$typedArray = $sut->getTypedArray("numbers", "float", fn() => []);
		foreach($typedArray as $value) {
			self::assertIsFloat($value);
		}
	}

	public function testGetTypedArray_floatFailure():void {
		$value = [1, "2", 3.000, "four"];
		$sut = $this->getSut([
			"numbers" => $value,
		]);
		self::expectException(TypeError::class);
		$sut->getTypedArray("numbers", "float", fn() => []);
	}

	public function testGetTypedArray_string():void {
		$value = [1, "2", 3.000, "four"];
		$sut = $this->getSut([
			"numbers" => $value,
		]);
		$typedArray= $sut->getTypedArray("numbers", "string", fn() => []);
		foreach($typedArray as $value) {
			self::assertIsString($value);
		}
	}

	public function testGetTypedArray_bool():void {
		$value = [0, "1", false, true, [], new StdClass()];
		$sut = $this->getSut([
			"booleans" => $value,
		]);
		$typedArray= $sut->getTypedArray("booleans", "bool", fn() => []);
		foreach($typedArray as $i => $value) {
			self::assertSame((bool)($i % 2), $value, $i);
		}
	}

	public function testGetTypedArray_class():void {
		$value = [new SplFileInfo(__FILE__), new SplFileInfo(__DIR__)];
		$sut = $this->getSut([
			"files" => $value,
		]);
		$typedArray= $sut->getTypedArray("files", SplFileInfo::class, fn() => []);
		foreach($typedArray as $value) {
			self::assertInstanceOf(SplFileInfo::class, $value);
		}
	}

	public function testGetTypedArray_classError():void {
		$value = [new SplFileInfo(__FILE__), new SplFixedArray(), new SplFileInfo(__DIR__)];
		$sut = $this->getSut([
			"files" => $value,
		]);
		self::expectExceptionMessage("Array value at key '1' is not an instance of SplFileInfo");
		self::expectException(TypeError::class);
		$sut->getTypedArray("files", SplFileInfo::class, fn() => []);
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
			fileAccess: $mockFileAccess,
		);
	}
}
