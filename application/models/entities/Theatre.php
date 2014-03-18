<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\entities;

/**
 * Description of Theatre
 *
 * @author intelWorX
 */
class Theatre extends StandardEntity {

    protected function initRelations() {
        $this->setOneToMany('nearbys', TheatreNearby::manager(), 'distance_km')
                ->setOneToMany('showtimes', Showtime::manager(), 'show_date DESC, show_time ASC');
    }

    public function getMoviesShowing() {
        throw new \RuntimeException("Yet to implement");
    }

    public static function getOrCreate($theatreData, $locationInfo = null) {
        $manager = self::manager();
        $findWhere = (new \DbTableWhere())
                        ->where('name', $theatreData['name'])
                        ->where('address', $theatreData['address'])
                        ;
        if(($foundTheatre = $manager->getEntityWhere($findWhere))){
            TheatreNearby::getOrCreate($locationInfo, $foundTheatre);
            return $foundTheatre;
        }
        
        if(!$theatreData['longitude'] || !$theatreData['latitude']){
            $geoCode = \models\services\LocationService::instance()->addressLookup($theatreData['address']);
            if($geoCode){
                $theatreData['longitude'] = $geoCode->found_longitude;
                $theatreData['latitude'] = $geoCode->found_latitude;
            }
        }
        
        $theatreId = $manager->createEntity($theatreData)->save();
        if($theatreId){
            $theatre = $manager->getEntity($theatreId);
            TheatreNearby::getOrCreate($locationInfo, $theatre);
            return $theatre;
        }
        return null;
    }
    
    public function getGeocode() {
        return new \models\Geocode($this->_data['latitude'], $this->_data['longitude']);
    }

}
