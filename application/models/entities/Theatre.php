<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\entities;
use models\services\LocationService;

/**
 * Description of Theatre
 *
 * @author intelWorX
 */
class Theatre extends StandardEntity
{

    public static function getOrCreate($theatreData, $locationInfo = null, $lookUpAddress = false, $computeDistance = false)
    {
        $manager = self::manager();
        $findWhere = (new \DbTableWhere())
            ->where('name', $theatreData['name'])
            ->where('address', $theatreData['address']);
        if (($foundTheatre = $manager->getEntityWhere($findWhere))) {
            TheatreNearby::getOrCreate($locationInfo, $foundTheatre, $computeDistance);
            return $foundTheatre;
        }

        if ($lookUpAddress && (!$theatreData['longitude'] || !$theatreData['latitude'])) {
            $geoCode = LocationService::instance()->addressLookup($theatreData['address']);
            if ($geoCode) {
                $theatreData['longitude'] = $geoCode->found_longitude;
                $theatreData['latitude'] = $geoCode->found_latitude;
            }
        }

        $theatreId = $manager->createEntity($theatreData)->save();
        if ($theatreId) {
            $theatre = $manager->getEntity($theatreId);
            TheatreNearby::getOrCreate($locationInfo, $theatre, $computeDistance);
            return $theatre;
        }

        return null;
    }

    public function getMoviesShowing()
    {
        throw new \RuntimeException("Yet to implement");
    }

    public function getGeocode()
    {
        return new \models\GeoLocation($this->_data['latitude'], $this->_data['longitude'], $this->_data['address']);
    }

    /**
     *
     * @param int $movie_id
     * @param string $date
     * @param string|\DbTableFunction $order
     * @return Showtime[]
     */
    public function getShowtimes($movie_id = null, $date = null, $order = null)
    {
        $showtimesWhere = new \DbTableWhere();
        if ($movie_id) {
            $showtimesWhere->where('movie_id', $movie_id);
        }

        if ($date) {
            $showtimesWhere->where('show_date', $date);
        }

        if (!$order) {
            $order = new \DbTableFunction('type,show_date,show_time');
        }

        $showtimesWhere->setOrderBy($order)
            ->where('theatre_id', $this->_data['id']);

        return Showtime::manager()->getEntitiesWhere($showtimesWhere);
    }

    protected function initRelations()
    {
        $this->setOneToMany('nearbys', TheatreNearby::manager(), 'distance_m')
            ->setOneToMany('showtimes', Showtime::manager(), 'show_date DESC, show_time ASC');
    }

}
