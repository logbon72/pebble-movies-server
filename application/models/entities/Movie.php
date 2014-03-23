<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\entities;

/**
 * Description of Movie
 *
 * @author intelWorX
 */
class Movie extends StandardEntity {

    const UPDATE_FREQUENCY = 43200;

    protected function initRelations() {
        $this->setOneToMany('showtimes', Showtime::manager());
    }

    public static function getOrCreate($movieData) {
        $manager = static::manager();
        $movie = $manager->getEntity($movieData['title'], 'title', null, true);
        if ($movie) {
            if((time() - strtotime($movie->last_updated)) > self::UPDATE_FREQUENCY){
                $movieData['last_updated'] = new \DbTableFunction("now()");
                $movie->update($movieData, 'id');
            }
            return $movie;
        }

        $movieId = $manager->createEntity($movieData)
                ->save();

        return $manager->getEntity($movieId);
    }

    /**
     * 
     * @param type $theatre_id
     * @param type $date
     * @param \DbTableFunction $order
     * @return Showtime[]
     */
    public function getShowtimes($theatre_id = null, $date = null, $order = null) {
        $showtimesWhere = new \DbTableWhere();
        if ($theatre_id) {
            $showtimesWhere->where('theatre_id', $theatre_id);
        }

        if ($date) {
            $showtimesWhere->where('show_date', $date);
        }

        if (!$order) {
            $order = new \DbTableFunction('type,show_date,show_time');
        }
        $showtimesWhere->setOrderBy($order)
                ->where('movie_id', $this->_data['id']);
        //echo $showtimesWhere; exit;
        return Showtime::manager()->getEntitiesWhere($showtimesWhere);
    }

}
