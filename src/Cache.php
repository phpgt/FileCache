<?php
namespace Gt\FileCache;

class Cache {
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
		int $secondsValid = 60 * 60 // 1 hour of validity
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
}
