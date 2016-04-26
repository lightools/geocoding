<?php

namespace Lightools\Geocoding;

/**
 * @author Jan Nedbal
 */
class NoExactResultException extends GeocodingException {

    /**
     * @var string
     */
    private $hint;

    /**
     * Additonal information, why exact result was not found
     * @return string
     */
    public function getHint() {
        return $this->hint;
    }

    /**
     * @param string $hint
     */
    public function setHint($hint) {
        $this->hint = $hint;
    }

}
