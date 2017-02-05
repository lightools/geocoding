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
class GoogleGeocoder implements IGeocoder {

    /**
     * @var string
     */
    const ENDPOINT_URL = 'https://maps.googleapis.com/maps/api/geocode/json';

    /**
     * @var string
     */
    const ERR_LIMIT_REACHED = 'OVER_QUERY_LIMIT';

    /**
     * @var string
     */
    const ERR_NO_RESULTS = 'ZERO_RESULTS';

    /**
     * @var string
     */
    const ERR_SERVER_ERROR = 'UNKNOWN_ERROR';

    /**
     * @var string
     */
    const PRECISION_EXACT = 'ROOFTOP';

    /**
     * @var IClient
     */
    private $httpClient;

    /**
     * @var string[]
     */
    private $parameters = [];

    public function __construct(IClient $httpClient) {
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

        $parameters = ['address' => (string) $address] + $this->parameters;
        $query = http_build_query($parameters);

        return $this->execute(self::ENDPOINT_URL . '?' . $query);
    }

    /**
     * Setup custom query parameters, e.g. ['components' => 'country:CZ']
     * @param string[] $parameters
     */
    public function setParameters(array $parameters) {
        $this->parameters = $parameters;
    }

    /**
     * @param string $url
     * @param boolean $retrying
     * @return Geocoded
     * @throws GeocodingFailedException
     * @throws QuotaLimitException
     * @throws NoExactResultException
     */
    private function execute($url, $retrying = FALSE) {

        try {
            $request = new Request(Request::GET, $url);
            $response = $this->httpClient->process($request);

            if ($response->getCode() !== Response::S200_OK) {
                throw new GeocodingFailedException('Unexpected HTTP code from Google geocoding API.');
            }

            $body = $response->getBody();
            $json = $this->parseBody($body);

        } catch (BadResponseException $e) {
            throw new GeocodingFailedException('HTTP request to Google geocoding API failed.', NULL, $e);
        }

        if ($json->status === self::ERR_LIMIT_REACHED) {
            throw new QuotaLimitException('Google geocoding API quota limit exceeded.');
        }

        if ($json->status === self::ERR_NO_RESULTS) {
            throw new NoExactResultException('Given address was not found at all!');
        }

        if ($json->status === self::ERR_SERVER_ERROR) {
            if ($retrying) {
                throw new GeocodingFailedException('Repeated server error received from Geocoding API!');
            } else {
                $this->execute($url, $retrying = TRUE);
            }
        }

        // process only first result
        $result = $json->results[0];

        if ($result->geometry->location_type !== self::PRECISION_EXACT) {
            throw new NoExactResultException('Exact address was not found!');
        }

        $data = [];
        foreach ($result->address_components as $component) {
            foreach ($component->types as $type) {
                $data = $this->updateAddressComponent($data, $type, $component);
            }
        }

        $lat = $result->geometry->location->lat;
        $lng = $result->geometry->location->lng;
        $city = isset($data['city']) ? $data['city'] : NULL;
        $postalCode = isset($data['postalCode']) ? str_replace(' ', '', $data['postalCode']) : NULL;
        $stateCode = isset($data['stateCode']) ? $data['stateCode'] : NULL;

        if (isset($data['street'])) {
            $street = $data['street'];
        } else {
            $street = $city;
        }

        if (isset($data['premise'])) {
            $street .= ' ' . $data['premise'];

            if (isset($data['streetNumber'])) {
                $street .= '/' . $data['streetNumber'];
            }

        } elseif (isset($data['streetNumber'])) {
            $street .= ' ' . $data['streetNumber'];
        }

        return new Geocoded($lat, $lng, $city, $street, $postalCode, $stateCode);
    }

    /**
     * @param array $data
     * @param string $type
     * @param stdClass $values
     * @return array
     */
    private function updateAddressComponent(array $data, $type, stdClass $values) {
        switch ($type) {
            case 'postal_code':
                $data['postalCode'] = $values->long_name;
                break;
            case 'locality':
                $data['city'] = $values->long_name;
                break;
            case 'premise':
                $data['premise'] = $values->long_name;
                break;
            case 'street_number':
                $data['streetNumber'] = $values->long_name;
                break;
            case 'route':
                $data['street'] = $values->long_name;
                break;
            case 'country':
                $data['stateCode'] = $values->short_name;
                break;
        }

        return $data;
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
            throw new GeocodingFailedException("Parsing JSON from Google geocoding API failed: $jsonErrorMessage", $jsonError);
        }

        return $data;
    }

}
