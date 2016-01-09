<?php
/**
 * Created by PhpStorm.
 * User: intelWorx
 * Date: 09/01/2016
 * Time: 12:59 PM
 */

namespace models\services;


use models\entities\GeocodeCached;
use models\GeoLocation;

class TimeZoneService extends \IdeoObject
{

    /**
     * @var TimeZoneServiceProvider[]
     */
    private $providers = [];


    /**
     * @var self
     */
    private static $instance;

    private function __construct()
    {
        //load service providers
        $directory = __DIR__ . DIRECTORY_SEPARATOR . 'tzproviders';
        $namespace = __NAMESPACE__ . '\\' . 'tzproviders';
        $packageScanner = new \PackageScanner($directory, $namespace, TimeZoneServiceProvider::getClass(), false);

        foreach ($packageScanner->getAllInstantiable() as $providerClass) {
            $this->providers[] = $providerClass->newInstance();
        }

        shuffle($this->providers);
    }

    /**
     * @return TimeZoneServiceProvider[]
     */
    public function getProviders()
    {
        return $this->providers;
    }


    /**
     * @param GeocodeCached $geocode
     * @param bool $forceUpdate
     * @return string timezone identifier
     */
    public function getTimeZone(GeocodeCached $geocode, $forceUpdate = false)
    {
        if (!$forceUpdate && $geocode->timezone) {
            return $geocode->timezone;
        }

        $timezone = $this->checkSingleCountryTimezone($geocode);
        if (!$timezone) {
            $timezone = $this->lookupTimeZoneByGeoCode($geocode->getGeocode());
        }

        //set time zone
        if ($timezone) {
            if ($geocode->city) {
                //update all cities wthin this country
                $where = \DbTableWhere::get()->where('country_iso', $geocode->country_iso)
                    ->where('city', $geocode->city);

                GeocodeCached::table()->update(['timezone' => $timezone], $where->getWhereString());
            } else {
                $geocode->update(['timezone' => $timezone]);
            }

        }

        return $timezone;
    }


    public function lookupTimeZoneByGeoCode(GeoLocation $geoLocation)
    {
        foreach ($this->providers as $serviceProvier) {
            $timezone = $serviceProvier->getTimeZone($geoLocation);
            if ($timezone) {
                return $timezone;
            } else {
                if (($lastError = $serviceProvier->getLastError(true))) {
                    \SystemLogger::warn("Error... {$lastError->getMessage()} TYPE: {$lastError->getType()}");
                }
            }
        }
    }

    /**
     * Checks if there's only one timezone attached to location's country.
     * @param string $countryIso
     * @return string|null string implies time only one timezone was found,
     * null implies either timezone was not found or it's more than one
     */
    public function checkSingleCountryTimezone($countryIso)
    {
        $timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, $countryIso);
        if ($timezones && count($timezones) === 1) {
            return current($timezones);
        }
        return null;

    }

    /**
     * Singleton instance of the service
     * @return TimeZoneService
     */
    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new TimeZoneService();
        }
        return self::$instance;
    }

}