<?php

namespace models\services;

/**
 * Description of LocationService
 *
 * @author intelWorX
 */
class LocationService extends \IdeoObject {

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
     * @var \models\entitymanagers\StandardEntityManager
     */
    protected $geocodeCachedManager;

    private function __construct() {
        $serviceProvidersDir = __DIR__ . DS . 'locationproviders';
        $directoryIterator = new \DirectoryIterator($serviceProvidersDir);
        while ($directoryIterator->valid()) {
            if ($directoryIterator->isFile() && $directoryIterator->isReadable()) {
                $className = __NAMESPACE__ . '\\locationproviders\\' . explode('.', $directoryIterator->getBasename())[0];
                if (class_exists($className)) {
                    $this->serviceProviderList[] = new $className();
                }
            }
            $directoryIterator->next();
        }

        if (!count($this->serviceProviderList)) {
            throw new \InvalidArgumentException("There are no service providers defined.");
        }

        \ComparableObjectSorter::sort($this->serviceProviderList, false, true);
        $this->geocodeCachedManager = \models\entities\GeocodeCached::manager();
    }

    /**
     * 
     * @param string $latLong
     * @param bool $forceReload
     * @return \models\entities\GeocodeCached Description
     * 
     */
    public function lookUp($latLong, $forceReload = false) {
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
    public function addressLookup($address, $extraData=array()) {
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
    public function postalCodeLookup($postalCode, $countryIso, $city = null) {
        $lookupWhere = new \DbTableWhere();
        $countryIso = LookupResult::remapIso($countryIso);
        $lookupWhere->where('country_iso', $countryIso);
        $postalCode =  str_replace(' ', '', $postalCode);
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
        $address = preg_replace('/\s+/', ' ', "{$postalCode} {$city} {$countryIso}");
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
    protected function cacheLookup(LookupResult $lookupResult, $preciseLong, $preciseLat, $extraData = array()) {
        $data = array(
            'longitude' => $preciseLong,
            'latitude' => $preciseLat,
                ) + $lookupResult->getCachingData($extraData);

        $saveId = $this->geocodeCachedManager->createEntity($data)->save(true);
        return $this->geocodeCachedManager->getEntity($saveId);
    }

    public function computeDistance(\models\GeoLocation $source, \models\GeoLocation $destination) {
        foreach ($this->serviceProviderList as $serviceProvier) {
            if (is_a($serviceProvier, '\models\services\LocationDistanceCheckI')) {
                /* @var $serviceProvier LocationDistanceCheckI */
                $distance = $serviceProvier->distanceLookup($source, $destination);
                if ($distance >= 0) {
                    return $distance;
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
        return -1;
    }

    /**
     * 
     * @return self
     */
    public static function instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

}
