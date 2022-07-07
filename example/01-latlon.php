<?php
/**
 * Two HTTP calls are in this script. The first is to get the public IP
 * address, the second is to look up that IP address in a geolocation database.
 * Both calls are costly operations, so are cached. Running this script will
 * output the inferred location coordinates, and a timer showing how many
 * seconds the operation took to complete. Running for a second time will take
 * close-to-zero seconds, as the data will be loaded from the cache.
 *
 * @noinspection PhpComposerExtensionStubsInspection
 */
require __DIR__ . "/../vendor/autoload.php";

$startTime = microtime(true);
$fileCache = new Gt\FileCache\Cache("/tmp/ip-address-geolocation");

function httpJson(string $uri):object {
	$ch = curl_init($uri);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	return json_decode(curl_exec($ch));
}

$ipAddress = $fileCache->get("ip", function():string {
	return httpJson("https://ipinfo.io")
		->ip;
});

$location = $fileCache->get("lat-lon", function()use($ipAddress):string {
	return httpJson("https://ipinfo.io/$ipAddress")
		->loc;
});

echo "Your IP is: $ipAddress", PHP_EOL;
echo "Your location is: $location", PHP_EOL;
echo "https://www.google.com/maps/?ll=$location&z=8&q=$location", PHP_EOL;

echo "Time taken: ",
	number_format(microtime(true) - $startTime, 3), " seconds.",
	PHP_EOL;
