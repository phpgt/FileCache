<?php
namespace Gt\FileCache;

class FileAccess {
	public function __construct(
		private readonly string $dirPath
	) {
	}

	public function getData(string $name):mixed {
		$filePath = "$this->dirPath/$name";
		if(!is_file($filePath)) {
			throw new FileNotFoundException($filePath);
		}

		$contents = file_get_contents($filePath);
		return unserialize($contents);
	}

	public function setData(string $name, mixed $value):void {
		if(!is_dir($this->dirPath)) {
			mkdir($this->dirPath, 0775, true);
		}
		$filePath = "$this->dirPath/$name";
		file_put_contents($filePath, serialize($value));
	}

	public function checkValidity(string $name, int $secondsValidity):void {
		$filePath = "$this->dirPath/$name";
		if(!is_file($filePath)) {
			throw new CacheInvalidException("$filePath (does not exist)");
		}
		$fileModifiedTimestamp = filemtime($filePath);
		if($fileModifiedTimestamp <= time() - $secondsValidity) {
			throw new CacheInvalidException($filePath);
		}
	}
}
