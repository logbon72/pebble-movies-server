<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace controllers;

/**
 * Description of AppBaseController
 *
 * @author intelWorX
 */
class AppBaseController extends \BaseController{
    
    public function doDefault() {
        $this->_request->redirect("http://pblweb.com/appstore/532eadd24e66a6b2a4000137/");
    }
//    
//    public function doAny() {
//        $locationService = \models\services\LocationService::instance();
//        $geoCached1 = $locationService->addressLookup("547 Riverside Drive, New York, NY");
//        $geoCached2 = $locationService->addressLookup("2309 Frederick Douglass Blvd., New York NY 10027");
//        $mapQuest = new \models\services\locationproviders\MapQuest();
//        debug_op($geoCached1->getGeocode());
//        debug_op($geoCached2->getGeocode());
//        debug_op($mapQuest->distanceLookup($geoCached1->getGeocode(), $geoCached2->getGeocode()), true);
//    }
}
