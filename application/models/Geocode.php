<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models;

/**
 * Description of Geocode
 *
 * @author intelWorX
 */
class Geocode extends \IdeoObject{
    //put your code here
    protected $longitude;
    protected $latitude;
    
    public function __construct($latitude, $longitude) {
        $this->longitude = $latitude;
        $this->latitude = $longitude;
        $this->setSynthesizeFields(true);
    }
    
}
