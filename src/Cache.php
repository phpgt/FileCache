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
			throw new TypeError("Value with key '$name' is not an array");
		}

		return $value;
	}

	/**
	 * @template T
	 * @param class-string<T> $className
	 * @return T
	 */
	public function getInstance(string $name, string $className, callable $callback):object {
		$value = $this->get($name, $callback);
		if(get_class($value) !== $className) {
			throw new TypeError("Value is not of type $className");
		}

		return $value;
	}
}
