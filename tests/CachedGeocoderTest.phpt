<?php

namespace Lightools\Tests;

use Lightools\Geocoding\CachedGeocoder;
use Lightools\Geocoding\Geocoded;
use Lightools\Geocoding\IGeocoder;
use Mockery;
use Tester\Assert;
use Tester\Environment;
use Tester\TestCase;

require __DIR__ . '/../vendor/autoload.php';

Environment::setup();

/**
 * @testCase
 * @author Jan Nedbal
 */
class CachedGeocoderTest extends TestCase {

    public function testCaching() {
        $tempDir = sys_get_temp_dir();
        $address = uniqid();
        $result = Mockery::mock(Geocoded::class);

        $geocoder1 = Mockery::mock(IGeocoder::class);
        $geocoder1->shouldReceive('geocode')->once()->with($address)->andReturn($result);

        $geocoder = new CachedGeocoder($geocoder1, $tempDir);
        $geocoded1 = $geocoder->geocode($address);
        $geocoded2 = $geocoder->geocode($address);

        Assert::same($result, $geocoded1);
        Assert::same($result, $geocoded2);
    }

    protected function tearDown() {
        parent::tearDown();
        Mockery::close();
    }

}

(new CachedGeocoderTest)->run();
