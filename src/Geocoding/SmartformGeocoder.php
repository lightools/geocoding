<?php

namespace Lightools\Geocoding;

use Bitbang\Http\BadResponseException;
use Bitbang\Http\IClient;
use Bitbang\Http\Request;
use Bitbang\Http\Response;
use stdClass;

/**
 * @author Jan Nedbal
 */
class SmartformGeocoder implements IGeocoder {

    /**
     * @var string
     */
    const ENDPOINT_URL = 'https://secure.smartform.cz/smartform-ws/validateAddress/v3';

    /**
     * @var string
     */
    const RESULT_OK = 'OK';

    /**
     * @var string
     */
    const RESULT_FAIL = 'FAIL';

    /**
     * @var string
     */
    const PRECISION_EXACT = 'HIT';

    /**
     * @var IClient
     */
    private $httpClient;

    /**
     * @var string
     */
    private $password;

    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @param string $password
     * @param IClient $httpClient
     */
    public function __construct($password, IClient $httpClient) {
        $this->password = $password;
        $this->httpClient = $httpClient;
    }

    /**
     * @param string $address
     * @return Geocoded
     * @throws GeocodingFailedException
     * @throws QuotaLimitException
     * @throws NoExactResultException
     */
    public function geocode($address) {

        $data = [
            'values' => ['WHOLE_ADDRESS' => (string) $address],
            'password' => $this->password,
        ] + $this->parameters;

        return $this->execute($data);
    }

    /**
     * Setup custom parameters, e.g. ['countries' => ['CZ']]
     * @param array $parameters
     */
    public function setParameters(array $parameters) {
        $this->parameters = $parameters;
    }

    /**
     * @param array $data
     * @return Geocoded
     * @throws GeocodingFailedException
     * @throws NoExactResultException
     */
    private function execute(array $data) {

        try {
            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ];
            $request = new Request(Request::POST, self::ENDPOINT_URL, $headers, json_encode($data));
            $response = $this->httpClient->process($request);

            if ($response->getCode() !== Response::S200_OK) {
                throw new GeocodingFailedException('Unexpected HTTP code from Smartform API.');
            }

            $body = $response->getBody();
            $json = $this->parseBody($body);

        } catch (BadResponseException $e) {
            throw new GeocodingFailedException('HTTP request to Smartform API failed.', NULL, $e);
        }

        if ($json->resultCode === self::RESULT_FAIL) {
            throw new GeocodingFailedException("Smartform geocoding failed. $json->errorMessage");
        }

        if ($json->result->type === self::PRECISION_EXACT) {
            $address = $json->result->addresses[0];

            $lat = $address->coordinates ? $address->coordinates->gpsLat : NULL;
            $lng = $address->coordinates ? $address->coordinates->gpsLng : NULL;
            $street = $address->values->FIRST_LINE;
            $city = $address->values->SECOND_LINE;
            $postalCode = $address->values->ZIP;
            $stateCode = $address->values->COUNTRY_CODE;

            return new Geocoded($lat, $lng, $city, $street, $postalCode, $stateCode);

        } else {
            $ex = new NoExactResultException('No exact address found!');

            if ($json->result->hint) {
                $ex->setHint($json->result->hint);
            }

            throw $ex;
        }
    }

    /**
     * @param string $body
     * @return stdClass
     * @throws GeocodingFailedException
     */
    private function parseBody($body) {
        $data = json_decode($body);
        $jsonError = json_last_error();

        if ($jsonError) {
            $jsonErrorMessage = json_last_error_msg();
            throw new GeocodingFailedException("Parsing JSON from Smartform API failed: $jsonErrorMessage", $jsonError);
        }

        return $data;
    }

}
