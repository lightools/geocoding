<?php

namespace Lightools\Geocoding;

/**
 * @author Jan Nedbal
 */
interface IGeocoder {

    /**
     * @param string $address The searched address query
     * @return Geocoded
     * @throws GeocodingFailedException
     * @throws QuotaLimitException
     * @throws NoExactResultException
     */
    public function geocode($address);

}
