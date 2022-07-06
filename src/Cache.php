<?php
namespace Gt\FileCache;

class Cache {
	public function __construct(
		private string $path = "cache"
	) {
		if(!is_dir($path)) {
			mkdir($path, 0775, true);
		}
	}

	public function get(string $name, callable $callback):mixed {
		$fileName = $this->path . "/$name";
		if(file_exists($fileName)) {
			return unserialize(file_get_contents($fileName));
		}

		$value = $callback();
		file_put_contents($fileName, serialize($value));
		return $value;
	}
}
