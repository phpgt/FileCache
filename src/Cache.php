<?php
namespace Gt\FileCache;

use DateTimeImmutable;
use DateTimeInterface;
use Gt\TypeSafeGetter\CallbackTypeSafeGetter;
use TypeError;

class Cache implements CallbackTypeSafeGetter {
	private FileAccess $fileAccess;

	public function __construct(
		string $path = "cache",
		private readonly int $secondsValid = 60 * 60, // 1 hour of validity by default
		?FileAccess $fileAccess = null,
	) {
		if(is_null($fileAccess)) {
			$fileAccess = new FileAccess($path);
		}
		$this->fileAccess = $fileAccess;
	}

	public function get(
		string $name,
		callable $callback,
	):mixed {
		try {
			$this->fileAccess->checkValidity($name, $this->secondsValid);
			return $this->fileAccess->getData($name);
		}
		catch(FileNotFoundException|CacheInvalidException) {
			$value = $callback();
			$this->fileAccess->setData($name, $value);
			return $value;
		}
	}

	public function getString(string $name, callable $callback):string {
		return (string)$this->get($name, $callback);
	}

	public function getInt(string $name, callable $callback):int {
		return (int)$this->get($name, $callback);
	}

	public function getFloat(string $name, callable $callback):float {
		return (float)$this->get($name, $callback);
	}

	public function getBool(string $name, callable $callback):bool {
		return (bool)$this->get($name, $callback);
	}

	public function getDateTime(string $name, callable $callback):DateTimeInterface {
		$value = $this->get($name, $callback);
		if($value instanceof DateTimeInterface) {
			return $value;
		}
		elseif(is_int($value)) {
			$dateTime = new DateTimeImmutable();
			return $dateTime->setTimestamp($value);
		}

		return new DateTimeImmutable($value);
	}

	public function getArray(string $name, callable $callback):array {
		$value = $this->get($name, $callback);
		if(!is_array($value)) {
			throw new TypeError("Data '$name' is not an array");
		}

		return $value;
	}

	/**
	 * @template T
	 * @param class-string<T> $className
	 * @return array<T>
	 */
	public function getTypedArray(string $name, string $className, callable $callback):array {
		$array = $this->get($name, $callback);
		if(!is_array($array)) {
			throw new TypeError("Data '$name' is not an array");
		}

		foreach($array as $key => $value) {
			$array[$key] = $this->validateAndConvertValue($value, $className, $key);
		}

		return $array;
	}

	/**
	 * @template T
	 * @param mixed $value
	 * @param class-string<T> $className
	 * @param string|int $key
	 * @return T
	 */
	private function validateAndConvertValue(mixed $value, string $className, string|int $key): mixed {
		return match(strtolower($className)) {
			"int", "integer" => $this->validateAndConvertInt($value, $key),
			"float", "double" => $this->validateAndConvertFloat($value, $key),
			"string" => $this->convertToString($value),
			"bool", "boolean" => $this->convertToBool($value),
			default => $this->validateInstance($value, $className, $key),
		};
	}

	/**
	 * @param mixed $value
	 * @param string|int $key
	 * @return int
	 */
	private function validateAndConvertInt(mixed $value, string|int $key): int {
		if(is_int($value)) {
			return $value;
		}

		if(is_numeric($value)) {
			return (int)$value;
		}

		throw new TypeError("Array value at key '$key' is not an integer");
	}

	/**
	 * @param mixed $value
	 * @param string|int $key
	 * @return float
	 */
	private function validateAndConvertFloat(mixed $value, string|int $key): float {
		if(is_float($value)) {
			return $value;
		}

		if(is_numeric($value)) {
			return (float)$value;
		}

		throw new TypeError("Array value at key '$key' is not a float");
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	private function convertToString(mixed $value): string {
		return (string)$value;
	}

	/**
	 * @param mixed $value
	 * @return bool
	 */
	private function convertToBool(mixed $value): bool {
		return (bool)$value;
	}

	/**
	 * @template T
	 * @param mixed $value
	 * @param class-string<T> $className
	 * @param string|int $key
	 * @return T
	 */
	private function validateInstance(mixed $value, string $className, string|int $key): object {
		if($value instanceof $className) {
			return $value;
		}

		throw new TypeError("Array value at key '$key' is not an instance of $className");
	}

	/**
	 * @template T
	 * @param class-string<T> $className
	 * @return T
	 */
	public function getInstance(string $name, string $className, callable $callback):object {
		$value = $this->get($name, $callback);
		if(get_class($value) !== $className) {
			throw new TypeError("Value is not an instance of $className");
		}

		return $value;
	}
}
