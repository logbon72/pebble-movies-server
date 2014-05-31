<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models;

use DbTableFunction;
use DbTableWhere;
use IdeoObject;
use models\entities\GeocodeCached;
use models\entities\Theatre;
use models\entities\TheatreNearby;
use models\services\LocationService;
use SystemLogger;

/**
 * Description of LocationUpdater
 *
 * @author JosephT
 */
class LocationUpdater extends IdeoObject {

    //put your code here
    const SLEEP_INTERVAL_MICRO_SECONDS = 700;
    const DISTANCE_FAILED = -2;

    /**
     *
     * @var GeocodeCached[]
     */
    private static $cachedGeocodes = array();

    public static function update($limit = 100) {
        SystemLogger::setVerbose(true);
        SystemLogger::info("Running location Update");
        $where = (new DbTableWhere())
                ->whereOr('distance_m', -1)
                ->whereOrString('distance_m IS NULL')
                ->setOrderBy('created_on')
                ->setLimitAndOffset($limit)
        ;

        $theatreNearBys = TheatreNearby::manager()
                ->getEntitiesWhere($where);

        SystemLogger::info("Nearbys found for update = ", count($theatreNearBys));

        $locationService = LocationService::instance();

        $successful = 0;
        foreach ($theatreNearBys as $theatreNearBy) {
            //var_dump($theatreNearBy);exit;
            $theatre = $theatreNearBy->theatre;
            $success = false;
            SystemLogger::info("Updating Nearby with ID ", $theatreNearBy->id, " PostalCode/ISO", $theatreNearBy->postal_code, "/", $theatreNearBy->country_iso);
            /* @var $theatre Theatre */
            if ($theatre) {
                $geocodeCached = self::getGeocodeCached($theatreNearBy);
                if ($geocodeCached) {
                    $distance = $locationService->computeDistance($theatre->getGeocode(), $geocodeCached->getGeocode());
                    if ($distance && $distance > 0) {
                        $success = $theatreNearBy->update(array('distance_m' => $distance), 'id');
                        SystemLogger::info("Distance computed as: ", $distance);
                    } else {
                        SystemLogger::info("Distance could not be computed");
                    }
                }
            }

            if (!$success) {
                $theatreNearBy->update(array('distance_m' => self::DISTANCE_FAILED), 'id');
            } else {
                $successful++;
            }
        }

        return $successful;
    }

    /**
     * 
     * @param \models\entities\TheatreNearby $tnb
     * @return GeocodeCached
     * 
     */
    public static function getGeocodeCached(TheatreNearby $tnb) {
        $queryWhere = new DbTableWhere();
        $queryWhere->where('country_iso', $tnb->country_iso);
        if ($tnb->postal_code) {
            $queryWhere->where(new DbTableFunction("REPLACE(postal_code, ' ', '')"), str_replace(' ', '', $tnb->postal_code));
        } else {
            $queryWhere->where('city', $tnb->city);
        }

        $key = $queryWhere->getWhereString();
        if (array_key_exists($key, self::$cachedGeocodes)) {
            return self::$cachedGeocodes[$key];
        } else {
            return self::$cachedGeocodes[$key] = GeocodeCached::manager()->getEntityWhere($queryWhere);
        }
    }

}
