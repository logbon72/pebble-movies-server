<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\services;

/**
 * Description of LookupResult
 *
 * @author intelWorX
 */
class LookupResult extends \IdeoObject{
    
    
    protected $postalCode;
    protected $city;
    protected $countryIso;
    protected $country;
    protected $foundLong;
    protected $foundLat;
    //protected $timezone;
    
    public function __construct($postalCode, $countryIso, $foundLong, $foundLat,$city=NULL, $country=null) {
        $this->postalCode = $postalCode;
        $this->countryIso= $countryIso;
        $this->city= $city;
        $this->country= $country;
        $this->foundLat = $foundLat;
        $this->foundLong = $foundLong;
        $this->setSynthesizeFields(true);
    }
    
    public function getCachingData() {
        return array(
            'postal_code' => $this->postalCode,
            'city' => $this->city,
            'country_iso' => $this->countryIso,
            'country' => $this->country,
            'found_longitude' => $this->foundLong,
            'found_latitude' => $this->foundLat,
        );
    }
    
    
    
}
