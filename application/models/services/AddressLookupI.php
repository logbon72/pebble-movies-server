<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\services;

/**
 * Description of AddressLookupI
 *
 * @author intelWorX
 */
interface AddressLookupI {
    /**
     * @return \models\Geocode Description
     */
    public function getGeocode($address);
}
