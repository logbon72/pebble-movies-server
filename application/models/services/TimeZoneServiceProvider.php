<?php
/**
 * Created by PhpStorm.
 * User: intelWorx
 * Date: 09/01/2016
 * Time: 1:02 PM
 */

namespace models\services;


use models\GeoLocation;

abstract class TimeZoneServiceProvider extends ServiceProvider
{

    /**
     *
     * @param GeoLocation $location
     * @return String the time zone identifier
     *
     * @throws ServiceError
     */
    abstract public function getTimeZone(GeoLocation $location);
}