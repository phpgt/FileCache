<?php
namespace GT\FileCache;

class FileAccess {
	public function __construct(
		private readonly string $dirPath
	) {
	}

	public function getData(string $name):mixed {
		$filePath = $this->getFilePath($name);
		if(!is_file($filePath)) {
			throw new FileNotFoundException($filePath);
		}

		$contents = file_get_contents($filePath);
		return unserialize($contents);
	}

	public function setData(string $name, mixed $value):void {
		$filePath = $this->getFilePath($name);
		if(!is_dir(dirname($filePath))) {
			mkdir(dirname($filePath), 0775, true);
		}
		file_put_contents($filePath, serialize($value));
	}

	public function checkValidity(string $name, int $secondsValidity):void {
		$filePath = $this->getFilePath($name);
		if(!is_file($filePath)) {
			throw new CacheInvalidException("$filePath (does not exist)");
		}
		$fileModifiedTimestamp = filemtime($filePath);
		if($fileModifiedTimestamp <= time() - $secondsValidity) {
			throw new CacheInvalidException($filePath);
		}
	}

	public function invalidate(string $name):void {
		$filePath = $this->getFilePath($name);
		if(!is_file($filePath)) {
			return;
		}

		unlink($filePath);
	}

	private function getFilePath(string $name):string {
		$escapedName = rawurlencode($name);
		return "$this->dirPath/$escapedName";
	}
}
