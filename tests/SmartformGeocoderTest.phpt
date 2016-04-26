<?php

namespace Lightools\Tests;

use Bitbang\Http\IClient;
use Bitbang\Http\Request;
use Bitbang\Http\Response;
use Lightools\Geocoding\GeocodingFailedException;
use Lightools\Geocoding\NoExactResultException;
use Lightools\Geocoding\SmartformGeocoder;
use Mockery;
use Mockery\MockInterface;
use Tester\Assert;
use Tester\Environment;
use Tester\TestCase;

require __DIR__ . '/../vendor/autoload.php';

Environment::setup();

/**
 * @testCase
 * @author Jan Nedbal
 */
class SmartformGeocoderTest extends TestCase {

    public function testFound() {
        $response = file_get_contents(__DIR__ . '/responses/smartform-exact.json');
        $client = $this->createClientMock($response);

        $geocoder = new SmartformGeocoder('pass', $client);

        $geocoded = $geocoder->geocode('Revoluční 7, Praha');

        Assert::same('Revoluční 724/7', $geocoded->getStreet());
        Assert::same('Praha 1 - Staré Město', $geocoded->getCity());
        Assert::same('CZ', $geocoded->getStateCode());
        Assert::same('11000', $geocoded->getPostalCode());
        Assert::equal(50.090356, $geocoded->getLatitude());
        Assert::equal(14.42769, $geocoded->getLongitude());
    }

    public function testNotFound() {
        $response = file_get_contents(__DIR__ . '/responses/smartform-approx.json');
        $client = $this->createClientMock($response);

        $geocoder = new SmartformGeocoder('pass', $client);

        Assert::exception(function () use ($geocoder) {
            $geocoder->geocode('Revoluční, Praha');
        }, NoExactResultException::class);
    }

    public function testHttpFail() {
        $client = Mockery::mock(IClient::class);
        $client->shouldReceive('process')->with(Request::class)->once()->andThrow(GeocodingFailedException::class);

        $geocoder = new SmartformGeocoder('pass', $client);

        Assert::exception(function () use ($geocoder) {
            $geocoder->geocode('Revoluční 7, Praha');
        }, GeocodingFailedException::class);
    }

    protected function tearDown() {
        parent::tearDown();
        Mockery::close();
    }

    /**
     * @param string $responseBody
     * @return MockInterface
     */
    private function createClientMock($responseBody) {
        $response = Mockery::mock(Response::class);
        $response->shouldReceive('getCode')->once()->andReturn(Response::S200_OK);
        $response->shouldReceive('getBody')->once()->andReturn($responseBody);

        $client = Mockery::mock(IClient::class);
        $client->shouldReceive('process')->with(Request::class)->once()->andReturn($response);

        return $client;
    }

}

(new SmartformGeocoderTest)->run();
