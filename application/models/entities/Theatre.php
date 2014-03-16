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
class Theatre extends StandardEntity{
    
    protected function initRelations() {
        $this->setOneToMany('nearbys', TheatreNearby::manager(), 'distance_km')
            ->setOneToMany('showtimes', Showtime::manager(), 'show_date DESC, show_time ASC');
    }
    
    public function getMoviesShowing() {
        throw new \RuntimeException("Yet to implement");
    }
    
}
