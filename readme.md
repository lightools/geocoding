## Introduction

Simple address geocoding with exact results only - may be used for full address validation.
For example address ```Revoluční 11, Praha``` will be found, but ```Revoluční, Praha``` will be not.

## Installation

```sh
$ composer require lightools/geocoding
```

## Simple Usage

```php
try {
    $httpClient = new Bitbang\Http\Clients\CurlClient();
    $geocoder = new Lightools\Geocoding\GoogleGeocoder($httpClient);
    $geocoded = $geocoder->geocode('Revoluční 11, Praha');

    echo $geocoded->getLatitude();
    echo $geocoded->getLongitude();
    echo $geocoded->getPostalCode();

} catch (Lightools\Geocoding\GeocodingFailedException $e) {
    // e.g. HTTP request failed
} catch (Lightools\Geocoding\NoExactResultException $e) {
    // invalid or inaccurate address
} catch (Lightools\Geocoding\QuotaLimitException $e) {
    // rate limit exceeded
}
```

### Chaining, caching, configuring

This library is shipped with simple caching geocoder and chain geocoder.
You can configure chain geocoder which exceptions will cause skipping to next geocoder by second parameter in constructor.
If you want to create your own, just implement interface ```IGeocoder```.

For Google geocoder, you can configure any query parameters you want, just call ```setParameters``` and setup for example language or components.
Smartform geocoder has the same method, but there not too much to configure - only target countries.

```php
$httpClient = new Bitbang\Http\Clients\CurlClient();

$googleGeocoder = new Lightools\Geocoding\GoogleGeocoder($httpClient);
$googleGeocoder->setParameters(['components' => 'country:CZ']);

$smartformGeocoder = new Lightools\Geocoding\SmartformGeocoder('password', $httpClient);
$smartformGeocoder->setParameters(['countries' => ['CZ']]);

$chainGeocoder = new Lightools\Geocoding\ChainGeocoder([$smartformGeocoder, $googleGeocoder]);
$cachedGeocoder = new Lightools\Geocoding\CachedGeocoder($chainGeocoder, __DIR__ . '/cache/geocoding');

$cachedGeocoder->geocode('Václavské náměstí 837/11, Praha');
```

## How to run tests

```sh
$ vendor/bin/tester tests
```
