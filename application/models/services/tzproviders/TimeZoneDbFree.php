<?php
/**
 * Created by PhpStorm.
 * User: intelWorx
 * Date: 09/01/2016
 * Time: 1:18 PM
 */

namespace models\services\tzproviders;


use models\GeoLocation;
use models\services\ProviderException;
use models\services\ServiceError;
use models\services\TimeZoneServiceProvider;

class TimeZoneDbFree extends TimeZoneServiceProvider
{


    const STATUS_OK = 'OK';
    const STATUS_ERR = 'FAIL';
    const DEFAULT_URL_TEMPLATE = "http://api.timezonedb.com/?lng={lng}&lat={lat}&format=json&key={apiKey}";

    private $apiKey;

    /**
     * TimeZoneDbFree constructor.
     */
    public function __construct()
    {
        $this->apiKey = \SystemConfig::getInstance()->timezone_db['api_key'];
        if (!$this->apiKey) {
            throw new ProviderException("API Key for Time Zone DB is not set");
        }
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
            'lng' => $location->getLongitude(),
            'lat' => $location->getLongitude(),
            'apiKey' => $this->apiKey,
        ]);

        $response = json_decode($this->callUrl($url));
        if ($response->status === self::STATUS_OK) {
            return $response->zoneName;
        }

        $this->lastError = new ServiceError(ServiceError::ERR_NOT_FOUND, $response->message);
        return null;
    }
}