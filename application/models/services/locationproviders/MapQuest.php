<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\services\locationproviders;

use models\GeoLocation;
//use models\services\AddressLookupI;
use models\services\LocationDistanceCheckI;
use models\services\LocationServiceProvider;
use models\services\ServiceError;
use SystemConfig;

/**
 * Description of MapQuest
 *
 * @author intelWorX
 */
class MapQuest extends LocationServiceProvider implements LocationDistanceCheckI/* , AddressLookupI */ {

    protected $apiKey;

    const URL_ADDRESS_LOOKUP = "http://open.mapquestapi.com/geocoding/v1/address?key={apiKey}&inFormat=kvp&outFormat=json&location={address}";
    const URL_LATLNG_LOOKUP = "http://open.mapquestapi.com/geocoding/v1/reverse?key={apiKey}&location={lat},{lng}";
    const URL_DISTANCE_LOOKUP = "http://open.mapquestapi.com/directions/v2/route?key={apiKey}&outFormat=json&routeType=fastest&timeType=0&narrativeType=none&enhancedNarrative=false&locale=en_US&unit=k&drivingStyle=2&highwayEfficiency=21.0&from={from}&to={to}&doReverseGeocode=false";

    public function __construct() {
        $this->priority = -1;
        $this->apiKey = SystemConfig::getInstance()->map_quest['api_key'];
    }

    public function distanceLookup(GeoLocation $source, GeoLocation $destination) {
        $data = array(
            'from' => urlencode($source->getLatitude() && $source->getLongitude() ? strval($source) : $source->getAddress()),
            'to' => urlencode($destination->getLatitude() && $destination->getLongitude() ? strval($destination) : $destination->getAddress()),
            //'to' => urlencode($destination->getAddress() ? : strval($destination)),
            'apiKey' => $this->apiKey,
        );

        $url = $this->formatUrl(self::URL_DISTANCE_LOOKUP, $data, true);
        $apiResult = json_decode($this->callUrl($url, false), true);
        if ($this->hasError($apiResult, false)) {
            return null;
        }

        $totalDist = 0;
        if (count($apiResult['route']['legs'])) {
            foreach ($apiResult['route']['legs'] as $leg) {
                $totalDist += $leg['distance'];
            }
            $totalDist = $totalDist * 1000; //cast to meters
        } else {
            $totalDist = -1;
        }
        return $totalDist;
    }

    public function addressLookup($address) {
        $data = array(
            'apiKey' => $this->apiKey,
            'address' => urlencode($address),
        );

        $url = $this->formatUrl(self::URL_ADDRESS_LOOKUP, $data, true);
        $apiResult = json_decode($this->callUrl($url, false), true);
        if ($this->hasError($apiResult)) {
            return null;
        }

        $locationResults = (array) $apiResult['results'][0]['locations'];
        return $this->convertToLookupResult($locationResults[0]);
    }

    protected function convertToLookupResult($result) {
        return new \models\services\LookupResult($result['postalCode'], $result['adminArea1'], $result['latLng']['lng'], $result['latLng']['lat'], $result['adminArea3']);
    }

    protected function hasError($apiResult, $checkLocations = true) {
        if ($apiResult['info']['statuscode']) {
            return $this->lastError = new ServiceError(ServiceError::ERR_RATE_LIMIT, $apiResult['info']['statuscode'] . ": " . join("\n", $apiResult['info']['messages']));
        }

        if ($checkLocations) {
            if (empty($apiResult['results'][0]['locations'])) {
                return $this->lastError = new ServiceError(ServiceError::ERR_NOT_FOUND);
            }
        }

        return null;
    }

    public function lookUp($long, $lat) {
        $data = array(
            'apiKey' => $this->apiKey,
            'lat' => $lat,
            'lng' => $long,
        );

        $url = $this->formatUrl(self::URL_LATLNG_LOOKUP, $data, true);
        $apiResult = json_decode($this->callUrl($url, false), true);
        if ($this->hasError($apiResult)) {
            return null;
        }

        $locationResults = (array) $apiResult['results'][0]['locations'];
        return $this->convertToLookupResult($locationResults[0]);
    }

//put your code here
}
