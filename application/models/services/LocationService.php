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
                $className = __NAMESPACE__ . '\\' . explode('.', $directoryIterator->getBasename())[0];
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
            if ($lookUpResult) {
                return $this->cacheLookup($lookUpResult, $preciseLong, $preciseLat);
            } else {
                if (($lastError = $serviceProvier->getLastError(true))) {
                    \SystemLogger::warn("Error... {$lastError->getMessage()} TYPE: {$lastError->getType()}");
                }
                if (!$lastError->isRateLimit()) {
                    break;
                }
            }
        }
        
        return null;
    }

    /**
     * 
     * @param \models\services\LookupResult $lookupResult
     * @param type $preciseLong
     * @param type $preciseLat
     * @return \models\entities\GeocodeCached
     */
    public function cacheLookup(LookupResult $lookupResult, $preciseLong, $preciseLat) {
        $data = array(
            'longitude' => $preciseLong,
            'latitude' => $preciseLat,
        ) + $lookupResult->getCachingData();

        $saveId = $this->geocodeCachedManager->createEntity($data)->save();
        return $this->geocodeCachedManager->getEntity($saveId);
    }

    
    public function computeDistance(\models\Geocode $source, \models\Geocode $destination) {
        throw new \RuntimeException("Unimplemented");
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
