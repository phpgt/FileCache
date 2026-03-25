<?php
require __DIR__ . "/../vendor/autoload.php";

use Gt\FileCache\Cache;

$fileCache = new Cache("/tmp/phpgt-filecache-datetime");

$dateTime = $fileCache->getInstance("current-date", DateTime::class, static function(): DateTime {
	return new DateTime();
});

echo $dateTime->format(DateTimeInterface::ATOM) . PHP_EOL;
