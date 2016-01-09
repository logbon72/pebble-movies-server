<?php

namespace models\services;

use models\GeoLocation;

/**
 * Description of LocationService
 *
 * @author intelWorX
 */
class LocationService extends \IdeoObject
{

    const EARTH_RADIUS = 6378137;
    //put your code here
    /**
     *
     * @var LocationService
     */
    private static $instance;

    const PROVIDERS_DIR = 'locationproviders';
    const GEOCODE_PRECISION = 3;

    /**
     *
     * @var LocationServiceProvider[]
     */
    protected $serviceProviderList = array();

    /**
     *
     * @var \ModelEntityManager
     */
    protected $geocodeCachedManager;

    private function __construct()
    {
        $serviceProvidersDir = __DIR__ . DS . 'locationproviders';
        $ns = __NAMESPACE__ . '\\locationproviders';

        $scanner = new \PackageScanner($serviceProvidersDir, $ns, LocationServiceProvider::getClass());

        foreach($scanner->getAllInstantiable() as $provider){
            $this->serviceProviderList[] = $provider->newInstance();
        }

        if (!count($this->serviceProviderList)) {
            throw new \InvalidArgumentException("There are no service providers defined.");
        }

        //\ComparableObjectSorter::sort($this->serviceProviderList, false, true);
        shuffle($this->serviceProviderList);
        $this->geocodeCachedManager = \models\entities\GeocodeCached::manager();
    }

    /**
     *
     * @param string $latLong
     * @param bool $forceReload
     * @return \models\entities\GeocodeCached Description
     *
     */
    public function lookUp($latLong, $forceReload = false)
    {
        list($lat, $long) = explode(',', $latLong);
        $preciseLat = round(doubleval($lat), self::GEOCODE_PRECISION);
        $preciseLong = round(doubleval($long), self::GEOCODE_PRECISION);

        if (!$forceReload) {
            $lookUpWhere = (new \DbTableWhere())
                ->where('longitude', $preciseLong)
                ->where('latitude', $preciseLat)
                ->setLimitAndOffset(1);
            $cachedList = \models\entities\GeocodeCached::manager()
                ->getEntitiesWhere($lookUpWhere);
            if (count($cachedList)) {
                return $cachedList[0];
            }
        }

        foreach ($this->serviceProviderList as $serviceProvier) {
            $lookUpResult = $serviceProvier->lookUp($preciseLong, $preciseLat);
            \SystemLogger::debug("Making call with: ", $serviceProvier->getClassBasic());
            if ($lookUpResult) {
                //var_dump($lookUpResult);exit;
                return $this->cacheLookup($lookUpResult, $preciseLong, $preciseLat);
            } else {

                if (($lastError = $serviceProvier->getLastError(true))) {
                    \SystemLogger::warn("Error: {$lastError->getMessage()} TYPE: {$lastError->getType()}");
                }

                if ($lastError && !$lastError->isRateLimit()) {
                    break;
                }
            }
        }

        return null;
    }

    /**
     *
     * @param type $address
     * @return null|\models\entities\GeocodeCached
     */
    public function addressLookup($address, $extraData = array(), $shuffle = false)
    {
        if ($shuffle) {
            shuffle($this->serviceProviderList);
        }

        foreach ($this->serviceProviderList as $serviceProvier) {
            if (is_a($serviceProvier, '\models\services\AddressLookupI')) {
                /* @var $serviceProvier AddressLookupI */
                $lookupResult = $serviceProvier->addressLookup($address);
                if ($lookupResult) {
                    return $this->cacheLookup($lookupResult, round($lookupResult->getFoundLong(), self::GEOCODE_PRECISION), round($lookupResult->getFoundLat(), self::GEOCODE_PRECISION), $extraData);
                } else {
                    if (($lastError = $serviceProvier->getLastError(true))) {
                        \SystemLogger::warn("Error... {$lastError->getMessage()} TYPE: {$lastError->getType()}");
                    }
                    if (!$lastError->isRateLimit()) {
                        break;
                    }
                }
            }
        }
        return null;
    }

    /**
     *
     * @param string $postalCode
     * @param string $countryIso
     * @param string $city
     * @return \models\entities\GeocodeCached
     */
    public function postalCodeLookup($postalCode, $countryIso, $city = null)
    {
        $lookupWhere = new \DbTableWhere();
        $countryIso = LookupResult::remapIso($countryIso);
        $lookupWhere->where('country_iso', $countryIso);
        $postalCode = str_replace(' ', '', $postalCode);
        if ($postalCode) {
            $lookupWhere->where(new \DbTableFunction("REPLACE(postal_code, ' ', '')"), $postalCode);
        } elseif ($city) {
            $lookupWhere->where('city', $city);
        }
        //var_dump($lookupWhere);exit;
        $cached = $this->geocodeCachedManager->getEntityWhere($lookupWhere);
        if ($cached) {
            return $cached;
        }
        $country = LookupResult::$ISO_TABLE[$countryIso];
        $address = preg_replace('/\s+/', ' ', "{$postalCode} {$city}, {$country}");
        return $this->addressLookup(trim($address), array(
            'postal_code' => $postalCode,
            'city' => $city,
            'country_iso' => $countryIso
        ));
    }

    /**
     *
     * @param \models\services\LookupResult $lookupResult
     * @param type $preciseLong
     * @param type $preciseLat
     * @return \models\entities\GeocodeCached
     */
    protected function cacheLookup(LookupResult $lookupResult, $preciseLong, $preciseLat, $extraData = array())
    {
        $data = array(
                'longitude' => $preciseLong,
                'latitude' => $preciseLat,
            ) + $lookupResult->getCachingData($extraData);

        if ($data['longitude'] && $data['longitude']) {
            $saveId = $this->geocodeCachedManager->createEntity($data)->save(true);
            return $this->geocodeCachedManager->getEntity($saveId);
        } else {
            return null;
        }
    }

    /**
     * @param GeoLocation $source
     * @param GeoLocation $destination
     * @param bool|true $shuffle
     * @return int
     */
    public function computeDistance(GeoLocation $source, GeoLocation $destination, $shuffle = true)
    {
        $dist = 0;
        if ($this->isUsingPhysicalDistance()) {
            $dist = $this->computePhysicalDistance($source, $destination, $shuffle);
        }

        if ($dist <= 0) {
            $dist = self::haversineGreatCircleDistance($source, $destination);
        }

        return $dist;
    }

    public function isUsingPhysicalDistance()
    {
        return !!\SystemConfig::getInstance()->service['physical_distance'];
    }

    private function computePhysicalDistance(GeoLocation $source, GeoLocation $destination, $shuffle = true)
    {
        if ($shuffle) {
            shuffle($this->serviceProviderList);
        }

        foreach ($this->serviceProviderList as $serviceProvier) {
            if (is_a($serviceProvier, '\models\services\LocationDistanceCheckI')) {
                /* @var $serviceProvier LocationDistanceCheckI */
                $distance = $serviceProvier->distanceLookup($source, $destination);
                if ($distance >= 0) {
                    return $distance;
                } else {
                    if (($lastError = $serviceProvier->getLastError(true))) {
                        \SystemLogger::warn("Error... {$lastError->getMessage()} TYPE: {$lastError->getType()}");
                        if (!$lastError->isRateLimit()) {
                            break;
                        }
                    }
                }
            }
        }
        return -1;
    }

    public static function haversineGreatCircleDistance(GeoLocation $from, GeoLocation $to)
    {
        // convert from degrees to radians
        $latFrom = deg2rad($from->getLatitude());
        $lonFrom = deg2rad($from->getLongitude());
        $latTo = deg2rad($to->getLatitude());
        $lonTo = deg2rad($to->getLongitude());

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
                cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return $angle * self::EARTH_RADIUS;
    }

    /**
     *
     * @return self
     */
    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

}
