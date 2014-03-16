<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\services;

/**
 * Description of LocationServiceProvider
 *
 * @author intelWorX
 */
abstract class LocationServiceProvider {

    /**
     *
     * @var LookupError
     * 
     */
    protected $lastError;

    /**
     * @return LookupResult Description
     */
    abstract public function lookUp($long, $lat);

    /**
     * 
     * @return LookupError
     */
    public function getLastError($clear = true) {
        $lastErr = $this->lastError;
        if($clear){
            $this->lastError = null;
        }
        return $lastErr;
    }

}
