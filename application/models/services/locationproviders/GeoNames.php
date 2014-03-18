<?php


namespace models\services\locationproviders;

use models\services\LocationServiceProvider;
use SystemConfig;

/**
 * Description of GeoNames
 *
 * @author intelWorX
 */
class GeoNames extends LocationServiceProvider {

    protected $username;

    const URL_NEARBY_POSTAL_CODE = "http://api.geonames.org/findNearbyPostalCodesJSON";
    const URL_NEARBY_ADDRESS = "http://api.geonames.org/findNearbyJSON";

    public function __construct() {
        $this->username = SystemConfig::getInstance()->geonames['username'];
        $this->priority = 10000;
    }

    /**
     * 
     * @param type $long
     * @param type $lat
     * @return \models\services\LookupResult
     * 
     * @todo Detect country before looking up to see if postal codes are accepted.
     * 
     */
    public function lookUp($long, $lat) {
        //"http://api.geonames.org/findNearbyJSON?lat=6.54839&lng=3.3841109&username=demo"
        $data = array(
            'lat' => $lat,
            'lng' => $long,
            'username' => $this->username,
            'maxRows' => 1,
        );

        $qString = http_build_query($data);
        $postCodeUrl = self::URL_NEARBY_POSTAL_CODE . '?' . $qString;
        //check for postal code support
        $postCodeResult = json_decode($this->callUrl($postCodeUrl), true);
        if (($serviceError = $this->checkError($postCodeResult, 'postalCodes')) && $serviceError->isRateLimit()) {
            return null;
        }

        if (!$serviceError) {
            $resp = $postCodeResult["postalCodes"][0];
            return new \models\services\LookupResult($resp['postalCode'], $resp['countryCode'], $resp['lng'], $resp['lat'], $resp['adminName1']);
        }

        //try using nearby placename
        $nearByUrl = self::URL_NEARBY_ADDRESS . '?' . $qString;
        $nearByResult = $this->callUrl($nearByUrl);
        if ($this->checkError($nearByResult, 'geonames')) {
            return null;
        } else {
            $resp = $nearByResult['geonames'][0];
        }

        return new \models\services\LookupResult('', $resp['countryCode'], $resp['lng'], $resp['lat'], $resp['adminName1']);
    }

    /**
     * 
     * @param type $result
     * @return \models\services\ServiceError|null
     */
    private function checkError($result, $key = '') {

        if (isset($result['status'])) {
            return $this->lastError = new \models\services\ServiceError(\models\services\ServiceError::ERR_RATE_LIMIT, $result['status']['message'] . "({$result['status']['value']})");
        }

        if (empty($result) || empty($result[$key])) {
            return $this->lastError = new \models\services\ServiceError(\models\services\ServiceError::ERR_NOT_FOUND, "Data was not found.");
        }

        return null;
    }

}
