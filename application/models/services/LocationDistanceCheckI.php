<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\services;

/**
 * Description of LocationDistanceCheckI
 *
 * @author intelWorX
 */
interface LocationDistanceCheckI {
    public function distanceLookup(\models\Geocode $source, \models\Geocode $destination);
}
