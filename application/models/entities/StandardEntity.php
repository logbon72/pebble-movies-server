<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\entities;

/**
 * Description of StandardEntity
 *
 * @author intelWorX
 */
class StandardEntity extends \Entity{
    //put your code here
    private static $instances = array();


    /**
     * 
     * @return \models\entitymanagers\StandardEntityManager
     */
    public static function manager() {
        $instanceKey = static::getClass();
        if(!array_key_exists($instanceKey, self::$instances)){
            self::$instances[$instanceKey] = new \models\entitymanagers\StandardEntityManager(static::getClassBasic());
        }
        
        return self::$instances[$instanceKey];
    }
}
