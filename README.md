Cache data in local files.
==========================

Short blurb.

***

<a href="https://github.com/PhpGt/FileCache/actions" target="_blank">
	<img src="https://badge.status.php.gt/filecache-build.svg" alt="Build status" />
</a>
<a href="https://app.codacy.com/gh/PhpGt/FileCache" target="_blank">
	<img src="https://badge.status.php.gt/filecache-quality.svg" alt="Code quality" />
</a>
<a href="https://app.codecov.io/gh/PhpGt/FileCache" target="_blank">
	<img src="https://badge.status.php.gt/filecache-coverage.svg" alt="Code coverage" />
</a>
<a href="https://packagist.org/packages/PhpGt/FileCache" target="_blank">
	<img src="https://badge.status.php.gt/filecache-version.svg" alt="Current version" />
</a>
<a href="http://www.php.gt/filecache" target="_blank">
	<img src="https://badge.status.php.gt/filecache-docs.svg" alt="PHP.Gt/FileCache documentation" />
</a>

## Example usage: get the latitude/longitude of the user's IP address

It's an expensive operation to make an HTTP call for every page view, but in this example we want to use a remote service to provide us with the estimated latitude/longitude of the current IP address.

The first time we see the IP address will have to make an HTTP call, but subsequent calls will be able to take advantage of the cache.

```php
$ipAddress = $_SERVER["REMOTE_ADDR"];
$fileCache = new Gt\FileCache\Cache("/tmp/ip-address-geolocation");

// This function uses file_get_contents to contact the remote server
// at ipinfo.io, a costly operation. We will pass the lookup function
// into the cache, so it is only called when we don't have a fresh result.
$lookup = function()use($ipAddress):string {
	$jsonString = file_get_contents("https://ipinfo.io/$ipAddress");
	$obj = json_decode($jsonString);
	return $obj->loc;
}

$location = $fileCache->get("lat-lon", $lookup);
echo "Your location is: $location";
```
