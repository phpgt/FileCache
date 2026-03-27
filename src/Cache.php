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
			try {
				$value = $callback();
				$this->fileAccess->setData($name, $value);
				return $value;
			}
			catch(CacheValueGenerationException) {
				return null;
			}
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

	/** @return array<mixed> */
	public function getArray(string $name, callable $callback):array {
		$value = $this->get($name, $callback);
		if(!is_array($value)) {
			throw new TypeError("Data '$name' is not an array");
		}

		return $value;
	}

	/**
	 * @template TObject of object
	 * @param class-string<TObject>|"int"|"integer"|"float"|"double"|"string"|"bool"|"boolean" $className
	 * @phpstan-return ($className is class-string<TObject> ? array<TObject> : ($className is "int"|"integer" ? array<int> : ($className is "float"|"double" ? array<float> : ($className is "string" ? array<string> : array<bool>))))
	 */
	public function getTypedArray(string $name, string $className, callable $callback):array {
		$array = $this->get($name, $callback);
		if(!is_array($array)) {
			throw new TypeError("Data '$name' is not an array");
		}

		/** @var array<int|string, mixed> $array */
		foreach($array as $key => $value) {
			$array[$key] = $this->validateAndConvertValue($value, $className, $key);
		}

		return $array;
	}

	/**
	 * @template TObject of object
	 * @param mixed $value
	 * @param class-string<TObject>|"int"|"integer"|"float"|"double"|"string"|"bool"|"boolean" $className
	 * @param string|int $key
	 * @phpstan-return ($className is class-string<TObject> ? TObject : ($className is "int"|"integer" ? int : ($className is "float"|"double" ? float : ($className is "string" ? string : bool))))
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
	 * @template TObject of object
	 * @param mixed $value
	 * @param class-string<TObject> $className
	 * @param string|int $key
	 * @return TObject
	 */
	private function validateInstance(mixed $value, string $className, string|int $key): object {
		if($value instanceof $className) {
			return $value;
		}

		throw new TypeError("Array value at key '$key' is not an instance of $className");
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return T
	 */
	public function getInstance(string $name, string $className, callable $callback):object {
		$value = $this->get($name, $callback);
		if(!$value instanceof $className) {
			throw new TypeError("Value is not an instance of $className");
		}

		/** @var T $value */
		return $value;
	}
}
