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

    //put your code here

    public static function getOrCreate($movieData) {
        $manager = static::manager();
        $movie = $manager->getEntity($movieData['title'], 'title');
        if($movie){
            return $movie;
        }
        
        $movieId = $manager->createEntity($movieData)
                        ->save();
        
        return $manager->getEntity($movieId);
    }

}
