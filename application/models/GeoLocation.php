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
 * @method float getLatitude();
 * @method float getLongitude();
 *
 */
class GeoLocation extends \IdeoObject {

    //put your code here
    protected $longitude;
    protected $latitude;
    protected $address;

    public function __construct($latitude, $longitude, $address = null) {
        $this->longitude = $longitude;
        $this->latitude = $latitude;
        $this->address = $address;
        $this->setSynthesizeFields(true);
    }

    public function getAddress() {
        return $this->address;
    }

    public function __toString() {
        return $this->latitude . ',' . $this->longitude;
    }

}
