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
class Movie extends StandardEntity
{

    const UPDATE_FREQUENCY = 43200;

    public static function getOrCreate($movieData)
    {
        $manager = static::manager();
        $entityWhere = (new \DbTableWhere())
            ->where('title', $movieData['title']);

        if (trim($movieData['rated'])) {
            $entityWhere->where('rated', trim($movieData['rated']));
        }

        $movie = $manager->getEntityWhere($entityWhere);
        if ($movie) {
            if ((time() - strtotime($movie->last_updated)) > self::UPDATE_FREQUENCY) {
                $movieData['last_updated'] = new \DbTableFunction("now()");
                $movie->update(array_filter($movieData), 'id');
            }
            return $movie;
        }

        $movieId = $manager->createEntity($movieData)
            ->save();

        return $manager->getEntity($movieId);
    }

    /**
     *
     * @param int $theatre_id
     * @param string $date
     * @param \DbTableFunction $order
     * @return Showtime[]
     */
    public function getShowtimes($theatre_id = null, $date = null, $order = null)
    {
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

    protected function initRelations()
    {
        $this->setOneToMany('showtimes', Showtime::manager());
    }

}
