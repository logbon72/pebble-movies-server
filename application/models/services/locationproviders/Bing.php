<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\services\locationproviders;

use models\GeoLocation;
use models\services\AddressLookupI;
use models\services\LocationDistanceCheckI;
use models\services\LocationServiceProvider;
use models\services\LookupResult;
use models\services\ServiceError;
use SystemConfig;

/**
 * Description of Bing
 *
 * @author intelWorX
 */
class Bing extends LocationServiceProvider implements AddressLookupI, LocationDistanceCheckI {

    protected $apiKey;

    const URL_LOOKUP = 'http://dev.virtualearth.net/REST/v1/Locations/{lat},{long}?o=json&key={apiKey}&maxResults=1';
    const URL_ADDRESS = 'http://dev.virtualearth.net/REST/v1/Locations?query={query}&key={apiKey}&maxResults=1';
    const URL_ROUTE = 'http://dev.virtualearth.net/REST/V1/Routes/Driving?output=json&wp.0={sourceLatLong}&wp.1={destLatLong}&key={apiKey}&du=km';
    const STATUS_SUCCESS = 200;

    public function __construct() {
        $this->apiKey = SystemConfig::getInstance()->bing['api_key'];
        $this->priority = 900;
    }

    public function distanceLookup(GeoLocation $source, GeoLocation $destination) {
        $data = array(
            "sourceLatLong" => $source->getAddress() ? : strval($source),
            "destLatLong" => $destination->getAddress()? : strval($destination),
            'apiKey' => $this->apiKey,
        );

        $result = $this->callUrl($this->formatUrl(self::URL_ROUTE, $data, false));
        $resultDecoded = json_decode($result, true);
        if ($this->checkError($resultDecoded)) {
            return -1;
        }

        $distance = $resultDecoded['resourceSets'][0]['resources'][0]['travelDistance'];
        return $distance ? $distance * 1000 : -1;
        //return 
    }

    public function addressLookup($address) {
        $data = array(
            "query" => $address,
            'apiKey' => $this->apiKey,
        );

        $result = $this->callUrl($this->formatUrl(self::URL_ADDRESS, $data));
        $resultDecoded = json_decode($result, true);
        if ($this->checkError($resultDecoded)) {
            return null;
        }

        return $this->convertToLookupResult($resultDecoded);
    }

    public function lookUp($long, $lat) {
        $data = array(
            'lat' => $lat,
            'long' => $long,
            'apiKey' => $this->apiKey,
        );

        $result = $this->callUrl($this->formatUrl(self::URL_LOOKUP, $data));
        $resultDecoded = json_decode($result, true);
        if ($this->checkError($resultDecoded)) {
            return null;
        }

        return $this->convertToLookupResult($resultDecoded);
    }

    /**
     * 
     * @param array $resultDecoded
     * @return LookupResult
     */
    private function convertToLookupResult($resultDecoded) {
        $resultProp = $resultDecoded['resourceSets'][0]['resources'][0];
        $address = $resultProp['address'];
        $geocode = $resultProp['geocodePoints'][0]['coordinates'];
        $countryIso = array_search($address['countryRegion'], LookupResult::$ISO_TABLE);
        if($countryIso){
            $countryIso = LookupResult::remapIso($countryIso);
        }
        return new LookupResult($address['postalCode'], $countryIso, $geocode[1], $geocode[0], $resultProp['adminDistrict'], $address['countryRegion']);
    }

    private function checkError($result, $key = 'resourceSets') {
        if ($result['statusCode'] !== self::STATUS_SUCCESS) {
            return $this->lastError = new ServiceError(ServiceError::ERR_RATE_LIMIT, $result['statusCode'] . ': ' . $result['statusDescription']);
        }

        if (empty($result[$key]) || $result[$key][0]['estimatedTotal'] < 1) {
            return $this->lastError = new ServiceError(ServiceError::ERR_NOT_FOUND, "No results found.");
        }

        return null;
    }

}
