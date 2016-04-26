<?php

namespace Lightools\Tests;

use Lightools\Geocoding\ChainGeocoder;
use Lightools\Geocoding\Geocoded;
use Lightools\Geocoding\GeocodingFailedException;
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
class ChainGeocoderTest extends TestCase {

    public function testSkipping() {
        $address = 'Revoluční 7, Praha';
        $result = Mockery::mock(Geocoded::class);

        $geocoder1 = Mockery::mock(IGeocoder::class);
        $geocoder1->shouldReceive('geocode')->once()->with($address)->andThrow(GeocodingFailedException::class);

        $geocoder2 = Mockery::mock(IGeocoder::class);
        $geocoder2->shouldReceive('geocode')->once()->with($address)->andReturn($result);

        $geocoder3 = Mockery::mock(IGeocoder::class);
        $geocoder3->shouldNotReceive('geocode');

        $geocoder = new ChainGeocoder([$geocoder1, $geocoder2, $geocoder3]);
        $geocoded = $geocoder->geocode($address);

        Assert::same($result, $geocoded);
    }

    public function testExceptionPropagation() {
        $address = 'Václavské náměstí 7, Praha';

        $geocoder1 = Mockery::mock(IGeocoder::class);
        $geocoder1->shouldReceive('geocode')->once()->with($address)->andThrow(GeocodingFailedException::class);

        $geocoder2 = Mockery::mock(IGeocoder::class);
        $geocoder2->shouldNotReceive('geocode');

        $geocoder = new ChainGeocoder([$geocoder1, $geocoder2], [ChainGeocoder::SKIP_QUOTA_LIMIT]);

        Assert::exception(function () use ($address, $geocoder) {
            $geocoder->geocode($address);
        }, GeocodingFailedException::class);
    }

    protected function tearDown() {
        parent::tearDown();
        Mockery::close();
    }

}

(new ChainGeocoderTest)->run();
