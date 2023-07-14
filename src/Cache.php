<?php
namespace Gt\FileCache;

use DateTimeImmutable;
use DateTimeInterface;
use Gt\TypeSafeGetter\CallbackTypeSafeGetter;
use TypeError;

class Cache implements CallbackTypeSafeGetter {
	const DEFAULT_SECONDS_VALID = 60 * 60; // 1 hour of validity.
	private FileAccess $fileAccess;

	public function __construct(
		string $path = "cache",
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
		int $secondsValid = self::DEFAULT_SECONDS_VALID
	):mixed {
		try {
			$this->fileAccess->checkValidity($name, $secondsValid);
			return $this->fileAccess->getData($name);
		}
		catch(FileNotFoundException|CacheInvalidException) {
			$value = $callback();
			$this->fileAccess->setData($name, $value);
			return $value;
		}
	}

	public function getString(string $name, callable $callback, int $secondsValid = self::DEFAULT_SECONDS_VALID):string {
		return (string)$this->get($name, $callback, $secondsValid);
	}

	public function getInt(string $name, callable $callback, int $secondsValid = self::DEFAULT_SECONDS_VALID):int {
		return (int)$this->get($name, $callback, $secondsValid);
	}

	public function getFloat(string $name, callable $callback, int $secondsValid = self::DEFAULT_SECONDS_VALID):float {
		return (float)$this->get($name, $callback, $secondsValid);
	}

	public function getBool(string $name, callable $callback, int $secondsValid = self::DEFAULT_SECONDS_VALID):bool {
		return (bool)$this->get($name, $callback, $secondsValid);
	}

	public function getDateTime(string $name, callable $callback, int $secondsValid = self::DEFAULT_SECONDS_VALID):DateTimeInterface {
		$value = $this->get($name, $callback, $secondsValid);
		if($value instanceof DateTimeInterface) {
			return $value;
		}
		elseif(is_int($value)) {
			$dateTime = new DateTimeImmutable();
			return $dateTime->setTimestamp($value);
		}

		return new DateTimeImmutable($value);
	}

	/**
	 * @template T
	 * @param class-string<T> $name
	 * @return T
	 */
	public function getInstance(string $name, string $className, callable $callback, int $secondsValid = self::DEFAULT_SECONDS_VALID):object {
		$serialized = $this->get($name, $callback, $secondsValid);
		$value = unserialize($serialized);
		if(get_class($value) !== $className) {
			throw new TypeError("Value is not of type $className");
		}

		return $value;
	}
}
