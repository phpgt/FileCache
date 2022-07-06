<?php
namespace Gt\FileCache;

class FileNotFoundException extends FileCacheException {
	public function __construct(string $name) {
	}
}
