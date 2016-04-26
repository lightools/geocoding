<?php

namespace Lightools\Geocoding;

/**
 * @author Jan Nedbal
 */
class ChainGeocoder implements IGeocoder {

    const SKIP_FAILURE = 1;
    const SKIP_QUOTA_LIMIT = 2;
    const SKIP_NO_RESULT = 3;

    /**
     * @var IGeocoder[]
     */
    private $geocoders;

    /**
     * Array of self::SKIP_*
     * @var int[]
     */
    private $skipEvents;

    /**
     * @param IGeocoder[] $geocoders
     * @param int[] $skipOn Which events causes skipping to next geocoder, array of self::SKIP_*
     */
    public function __construct(array $geocoders, array $skipOn = [self::SKIP_FAILURE, self::SKIP_QUOTA_LIMIT]) {
        $this->geocoders = $geocoders;
        $this->skipEvents = $skipOn;
    }

    /**
     * @param string $address
     * @return Geocoded
     * @throws GeocodingFailedException
     * @throws QuotaLimitException
     * @throws NoExactResultException
     */
    public function geocode($address) {

        $geocoders = $this->geocoders;
        $lastGeocoder = array_pop($geocoders);

        foreach ($geocoders as $geocoder) {
            try {
                return $geocoder->geocode($address);

            } catch (GeocodingFailedException $ex) {
                if (!in_array(self::SKIP_FAILURE, $this->skipEvents)) {
                    throw $ex;
                }

            } catch (QuotaLimitException $ex) {
                if (!in_array(self::SKIP_QUOTA_LIMIT, $this->skipEvents)) {
                    throw $ex;
                }

            } catch (NoExactResultException $ex) {
                if (!in_array(self::SKIP_NO_RESULT, $this->skipEvents)) {
                    throw $ex;
                }
            }
        }

        return $lastGeocoder->geocode($address);
    }

}
