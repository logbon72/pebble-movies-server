<?php
/**
 * Created by PhpStorm.
 * User: intelWorx
 * Date: 09/01/2016
 * Time: 2:53 PM
 */

namespace models\services\tzproviders;


use models\GeoLocation;
use models\services\GoogleMapServiceI;
use models\services\GoogleMapServiceT;
use models\services\ServiceError;
use models\services\TimeZoneServiceProvider;

class GoogleMap extends TimeZoneServiceProvider implements GoogleMapServiceI
{
    use GoogleMapServiceT;

    const DEFAULT_URL_TEMPLATE = "https://maps.googleapis.com/maps/api/timezone/json?location={geoCode}&key={apiKey}&timestamp={timestamp}";


    /**
     * GoogleMap constructor.
     */
    public function __construct()
    {
        $this->setApiKey();
    }


    /**
     *
     * @param GeoLocation $location
     * @return String the time zone identifier
     *
     * @throws ServiceError
     */
    public function getTimeZone(GeoLocation $location)
    {
        $url = $this->formatUrl(self::DEFAULT_URL_TEMPLATE, [
            'geoCode' => strval($location),
            'apiKey' => $this->apiKey,
            'timestamp' => time(),
        ]);

        $response = json_decode($this->callUrl($url), true);
        if (!$this->hasError($response)) {
            return $response['timeZoneId'];
        }

        return null;
    }
}