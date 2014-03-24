<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace main\controllers;

/**
 * Description of HomeController
 *
 * @author intelWorX
 */
class HomeController extends \controllers\AppBaseController {
    //put your code here
    
    public function doDefault() {
        $this->_request->redirect("http://pblweb.com/appstore/532eadd24e66a6b2a4000137/");
    }
    
//    public function doIso() {
//        $reverse = array();
//        foreach (\models\services\LookupResult::$ISO_TABLE as $code=>$country){
//            if(!array_key_exists($country, $reverse)){
//                $reverse[$country] = array();
//            }
//            $reverse[$country][] = $code;
//        }
//        
//        var_dump($reverse);
//        exit;
//    }
}
