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
    public function addressLookup($address) {
        foreach ($this->serviceProviderList as $serviceProvier) {
            if (is_a($serviceProvier, '\models\services\AddressLookupI')) {
                /* @var $serviceProvier AddressLookupI */
                $lookupResult = $serviceProvier->addressLookup($address);
                if ($lookupResult) {
                    return $this->cacheLookup($lookupResult, round($lookupResult->getFoundLong(), self::GEOCODE_PRECISION), round($lookupResult->getFoundLat(), self::GEOCODE_PRECISION));
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
        $lookupWhere->where('country_iso', $countryIso);
        if ($postalCode) {
            $lookupWhere->where('postal_code', $postalCode);
        }

        if ($city) {
            $lookupWhere->where('country_iso', $countryIso);
        }

        $cached = $this->geocodeCachedManager->getEntityWhere($lookupWhere);
        if ($cached) {
            return $cached;
        }
        $address = preg_replace('/\s+/', ' ', "{$postalCode} {$city} {$countryIso}");
        return $this->addressLookup(trim($address));
    }

    /**
     * 
     * @param \models\services\LookupResult $lookupResult
     * @param type $preciseLong
     * @param type $preciseLat
     * @return \models\entities\GeocodeCached
     */
    protected function cacheLookup(LookupResult $lookupResult, $preciseLong, $preciseLat) {
        $data = array(
            'longitude' => $preciseLong,
            'latitude' => $preciseLat,
                ) + $lookupResult->getCachingData();

        $saveId = $this->geocodeCachedManager->createEntity($data)->save();
        return $this->geocodeCachedManager->getEntity($saveId);
    }

    public function computeDistance(\models\Geocode $source, \models\Geocode $destination) {
        foreach ($this->serviceProviderList as $serviceProvier) {
            if (is_a($serviceProvier, '\models\services\LocationDistanceCheckI')) {
                /* @var $serviceProvier LocationDistanceCheckI*/
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