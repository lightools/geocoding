<?php

namespace Lightools\Geocoding;

/**
 * @author Jan Nedbal
 */
class CachedGeocoder implements IGeocoder {

    /**
     * @var string
     */
    private $cacheFile;

    /**
     * @var IGeocoder
     */
    private $geocoder;

    /**
     * Indexed by addresses
     * @var Geocoded[]
     */
    private $results = [];

    /**
     * @param IGeocoder $geocoder
     * @param string $tempDir
     */
    public function __construct(IGeocoder $geocoder, $tempDir) {
        $this->geocoder = $geocoder;
        $this->cacheFile = rtrim($tempDir, '/') . '/geocoding.dat';
    }

    /**
     * @param string $address
     * @return Geocoded
     * @throws GeocodingFailedException
     * @throws QuotaLimitException
     * @throws NoExactResultException
     */
    public function geocode($address) {

        $results = $this->getCachedResults();

        if (isset($results[$address])) {
            return $results[$address];
        }

        $geocoded = $this->geocoder->geocode($address);

        $this->results[$address] = $geocoded;
        $this->updateCachedResults($geocoded);

        return $geocoded;
    }

    /**
     * @return Geocoded[]
     */
    private function getCachedResults() {
        if (!$this->results && is_file($this->cacheFile)) {
            $this->results = unserialize(file_get_contents($this->cacheFile));
        }

        return $this->results;
    }

    /**
     * Save current cached results to cache file
     */
    private function updateCachedResults() {
        @mkdir(dirname($this->cacheFile), 0777, TRUE); // @ - may exist
        file_put_contents($this->cacheFile, serialize($this->results));
    }

}
