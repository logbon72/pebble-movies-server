<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\services\locationproviders;

/**
 * Description of Google
 *
 * @author intelWorX
 */
class Google extends \models\services\LocationServiceProvider implements \models\services\AddressLookupI {
    
    protected $apiKey;
    public function __construct() {
        $this->priority = 10;
        $this->apiKey = \SystemConfig::getInstance()->google['api_key'];
    }
    
    public function addressLookup($address) {
        throw new \Exception("Unimplemented");
    }

    public function lookUp($long, $lat) {
        throw new \Exception("Unimplemented");
    }

//put your code here
}
