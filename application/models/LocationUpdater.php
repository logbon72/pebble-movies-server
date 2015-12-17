<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models;

use models\entities\GeocodeCached;
use models\entities\Theatre;
use models\entities\TheatreNearby;
use models\services\LocationService;

/**
 * Description of LocationUpdater
 *
 * @author JosephT
 */
class LocationUpdater extends \IdeoObject
{

    //put your code here
    const SLEEP_INTERVAL_MICRO_SECONDS = 700;
    const DISTANCE_FAILED = -2;

    /**
     *
     * @var GeocodeCached[]
     */
    private static $cachedGeocodes = [];

    /**
     * @param int $limit
     * @return int
     */
    public static function update($limit = 100)
    {
        \SystemLogger::setVerbose(true);
        \SystemLogger::info("Running location Update");
        $where = (new \DbTableWhere())
            ->whereOrString('distance_m <= 0')
            ->whereOrString('distance_m IS NULL')
            ->setOrderBy('created_on')
            ->setLimitAndOffset($limit);

        $theatreNearBys = TheatreNearby::manager()
            ->getEntitiesWhere($where);

        \SystemLogger::info("Nearbys found for update = ", count($theatreNearBys));

        $locationService = LocationService::instance();
        $seenTheatres = [];
        $successful = 0;
        foreach ($theatreNearBys as $theatreNearBy) {
            //var_dump($theatreNearBy);exit;
            $theatre = $theatreNearBy->theatre;
            $success = false;
            \SystemLogger::info("Updating Nearby with ID ", $theatreNearBy->id, " PostalCode/ISO", $theatreNearBy->postal_code, "/", $theatreNearBy->country_iso, "Theatre:", $theatre->name, $theatre->address);
            /* @var $theatre Theatre */
            if ($theatre) {
                if (!($theatre->latitude && $theatre->longitude) && !in_array($theatre->id, $seenTheatres)) {
                    $seenTheatres[] = $theatre->id;
                    if ($theatre->address) {
                        self::setTheatreLatLng($theatre);
                    }
                }

                if ($theatre->address || ($theatre->latitude && $theatre->longitude)) {
                    $geocodeCached = self::getGeocodeCached($theatreNearBy);
                    if ($geocodeCached) {
                        $distance = $locationService->computeDistance($theatre->getGeocode(), $geocodeCached->getGeocode());
                        if ($distance >= 0) {
                            $success = $theatreNearBy->update(['distance_m' => max([$distance, 100])], 'id');
                            \SystemLogger::info("Distance computed as: ", $distance);
                        } else {
                            \SystemLogger::info("Distance could not be computed");
                        }
                    }
                }
            }

            if (!$success) {
                $theatreNearBy->update(['distance_m' => self::DISTANCE_FAILED], 'id');
            } else {
                $successful++;
            }
        }

        return $successful;
    }

    /**
     * @param Theatre $theatre
     * @return bool|mixed|\mysqli_result
     */
    public static function setTheatreLatLng(Theatre $theatre)
    {
        //51.4752267,-0.2396438
        \SystemLogger::info("Finding LongLat for: ", $theatre->name, $theatre->address);
        $geocode = LocationService::instance()->addressLookup($theatre->address, [], true);
        $saved = false;
        if ($geocode) {
            $geoLocation = $geocode->getGeocode();
            \SystemLogger::info("Found Geocode: ", strval($geoLocation));
            $saved = $theatre->update([
                'longitude' => $geoLocation->getLongitude(),
                'latitude' => $geoLocation->getLatitude(),
            ], 'id');
        }

        return $saved;
    }

    /**
     *
     * @param TheatreNearby $tnb
     * @return GeocodeCached
     *
     */
    public static function getGeocodeCached(TheatreNearby $tnb)
    {
        $queryWhere = new \DbTableWhere();
        $queryWhere->where('country_iso', $tnb->country_iso);
        if ($tnb->postal_code) {
            $queryWhere->where(new \DbTableFunction("REPLACE(postal_code, ' ', '')"), str_replace(' ', '', $tnb->postal_code));
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
