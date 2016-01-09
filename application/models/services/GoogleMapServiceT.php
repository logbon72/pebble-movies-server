<?php
/**
 * Created by PhpStorm.
 * User: intelWorx
 * Date: 09/01/2016
 * Time: 3:00 PM
 */

namespace models\services;


trait GoogleMapServiceT
{

    protected $apiKey;

    /**
     * @var ServiceError
     */
    protected $lastError;

    protected function setApiKey()
    {
        $this->apiKey = \SystemConfig::getInstance()->google['api_key'];
        if (!$this->apiKey) {
            throw new ProviderException("Google Maps API Key was not set");
        }
    }

    public function hasError($apiResult)
    {
        $status = $apiResult['status'];
        if ($status !== self::STATUS_OK) {
            if ($status === self::STATUS_ZERO_RESULTS) {
                return $this->lastError = new ServiceError(ServiceError::ERR_NOT_FOUND, self::STATUS_ZERO_RESULTS);
            } else {
                return $this->lastError = new ServiceError(ServiceError::ERR_RATE_LIMIT, $status);
            }
        }

        return null;
    }

}