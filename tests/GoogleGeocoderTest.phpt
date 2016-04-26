<?php

namespace Lightools\Tests;

use Bitbang\Http\IClient;
use Bitbang\Http\Request;
use Bitbang\Http\Response;
use Lightools\Geocoding\GeocodingFailedException;
use Lightools\Geocoding\GoogleGeocoder;
use Lightools\Geocoding\NoExactResultException;
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
class GoogleGeocoderTest extends TestCase {

    public function testFound() {
        $response = file_get_contents(__DIR__ . '/responses/google-exact.json');
        $client = $this->createClientMock($response);

        $geocoder = new GoogleGeocoder($client);

        $geocoded = $geocoder->geocode('Revoluční 7, Praha');

        Assert::same('Revoluční 724/7', $geocoded->getStreet());
        Assert::same('Praha', $geocoded->getCity());
        Assert::same('CZ', $geocoded->getStateCode());
        Assert::same('11000', $geocoded->getPostalCode());
        Assert::equal(50.0903598, $geocoded->getLatitude());
        Assert::equal(14.4276919, $geocoded->getLongitude());
    }

    public function testNotFound() {
        $response = file_get_contents(__DIR__ . '/responses/google-approx.json');
        $client = $this->createClientMock($response);

        $geocoder = new GoogleGeocoder($client);

        Assert::exception(function () use ($geocoder) {
            $geocoder->geocode('Revoluční, Praha');
        }, NoExactResultException::class);
    }

    public function testHttpFail() {
        $client = Mockery::mock(IClient::class);
        $client->shouldReceive('process')->with(Request::class)->once()->andThrow(GeocodingFailedException::class);

        $geocoder = new GoogleGeocoder($client);

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

(new GoogleGeocoderTest)->run();
