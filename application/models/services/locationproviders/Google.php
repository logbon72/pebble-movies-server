<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\services\locationproviders;

use models\services\AddressLookupI;
use models\services\GoogleMapServiceI;
use models\services\GoogleMapServiceT;
use models\services\LocationServiceProvider;
use models\services\LookupResult;

/**
 * Description of Google
 *
 * @author intelWorX
 */
class Google extends LocationServiceProvider implements AddressLookupI, GoogleMapServiceI
{

    use GoogleMapServiceT;


    const URL_ADDRESS_LOOKUP = "https://maps.googleapis.com/maps/api/geocode/json?address={address}&sensor=true&key={apiKey}";
    const URL_LATLNG_LOOKUP = "https://maps.googleapis.com/maps/api/geocode/json?latlng={latlng}&sensor=true&key={apiKey}";


    public function __construct()
    {
        $this->priority = 1000;
        $this->setApiKey();
    }

    /**
     *
     * @param string $address
     *
     * @return LookupResult result
     */
    public function addressLookup($address)
    {
        $data = array(
            'address' => $address,
            'apiKey' => $this->apiKey,
        );

        $url = $this->formatUrl(self::URL_ADDRESS_LOOKUP, $data);
        $apiResult = json_decode($this->callUrl($url), true);
        if (!$this->hasError($apiResult)) {
            return $this->convertToLookUpResult($apiResult['results'][0]);
        }

        return null;
    }

    protected function convertToLookUpResult($result)
    {
        if (!$result) {
            return null;
        }

        //$postalCode, $countryIso, $foundLong, $foundLat, $city, $country 
        $lData = array();

        foreach ($result['address_components'] as $component) {
            $types = $component['types'];
            if (!$lData['city'] && in_array("locality", $types)) {
                $lData['city'] = $component['long_name'];
            } else if (!$lData['city'] && in_array("administrative_area_level_1", $types)) {
                $lData['city'] = $component['long_name'];
            } else if (!$lData['city'] && in_array("administrative_area_level_2", $types)) {
                $lData['city'] = $component['long_name'];
            } else if (!$lData['country'] && in_array("country", $types)) {
                $lData['countryIso'] = $component['short_name'];
                $lData['country'] = $component['long_name'];
            } else if (!$lData['postalCode'] && in_array("postal_code", $types)) {
                $lData['postalCode'] = $component['short_name'];
            }
        }

        $latLngArr = $result['geometry']['location'];

        return new LookupResult($lData['postalCode'], $lData['countryIso'], $latLngArr['lng'], $latLngArr['lat'], $lData['city'], $lData['country']);
    }

    public function lookUp($long, $lat)
    {
        $data = array(
            'latlng' => "{$lat},{$long}",
            'apiKey' => $this->apiKey,
        );

        $url = $this->formatUrl(self::URL_LATLNG_LOOKUP, $data);
        $apiResult = json_decode($this->callUrl($url), true);
        if (!$this->hasError($apiResult)) {
            return $this->convertToLookUpResult($apiResult['results'][0]);
        }

        return null;
    }

//put your code here
}
