<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\services\locationproviders;

use Exception;
use models\Geocode;
use models\services\AddressLookupI;
use models\services\LocationDistanceCheckI;
use models\services\LocationServiceProvider;

/**
 * Description of MapQuest
 *
 * @author intelWorX
 */
class MapQuest extends LocationServiceProvider implements LocationDistanceCheckI, AddressLookupI {

    protected $apiKey;
    public function __construct() {
        $this->apiKey = \SystemConfig::getInstance()->map_quest['api_key'];
    }
    
    public function distanceLookup(Geocode $source, Geocode $destination) {
        throw new Exception("Unimplemented");
    }

    public function getGeocode($address) {
        throw new Exception("Unimplemented");
    }

    public function lookUp($long, $lat) {
        
    }

//put your code here
}
