<?php

namespace Lightools\Geocoding;

/**
 * @author Jan Nedbal
 */
class Geocoded {

    /**
     * @var float
     */
    private $latitude;

    /**
     * @var float
     */
    private $longitude;

    /**
     * @var string
     */
    private $city;

    /**
     * @var string
     */
    private $street;

    /**
     * @var string
     */
    private $postalCode;

    /**
     * @var string
     */
    private $stateCode;

    /**
     * @param float $latitude
     * @param float $longitude
     * @param string $city
     * @param string $street
     * @param string $postalCode
     * @param string $stateCode
     */
    public function __construct($latitude, $longitude, $city, $street, $postalCode, $stateCode) {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->city = $city;
        $this->street = $street;
        $this->postalCode = $postalCode;
        $this->stateCode = $stateCode;
    }

    /**
     * @return float
     */
    public function getLatitude() {
        return $this->latitude;
    }

    /**
     * @return float
     */
    public function getLongitude() {
        return $this->longitude;
    }

    /**
     * @return string
     */
    public function getCity() {
        return $this->city;
    }

    /**
     * Street with street number
     * @return string
     */
    public function getStreet() {
        return $this->street;
    }

    /**
     * @return string
     */
    public function getPostalCode() {
        return $this->postalCode;
    }

    /**
     * ISO 3166-2
     * @return string
     */
    public function getStateCode() {
        return $this->stateCode;
    }

}
