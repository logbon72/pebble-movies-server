<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\entities;

use models\services\LocationService;

/**
 * Description of TheatreNearby
 *
 * @author intelWorX
 */
class TheatreNearby extends StandardEntity {

    //put your code here
    protected function initRelations() {
        $this->setManyToOne('theatre', Theatre::manager());
    }

    public static function getOrCreate(GeocodeCached $locationInfo, Theatre $theatre, $computeDistance = false) {
        $where = $locationInfo->getQueryWhere()
                ->where('theatre_id', $theatre->id);

        $manager = static::manager();
        $nearby = $manager->getEntityWhere($where);
        if ($nearby) {
            return $nearby;
        }

        $data = $locationInfo->toArray(0, 2, array('country_iso', 'postal_code', 'country', 'city'));
        $data['distance_m'] = $computeDistance ? LocationService::instance()->computeDistance($locationInfo->getGeocode(), $theatre->getGeocode()) : -1;
        $data['theatre_id'] = $theatre->id;
        $nearbyId = $manager->createEntity($data)->save();
        return $nearbyId ? $manager->getEntity($nearbyId) : null;
    }

}
