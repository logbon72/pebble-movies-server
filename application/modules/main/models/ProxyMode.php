<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace main\models;

/**
 * Description of ProxyMode
 *
 * @author JosephT
 */
class ProxyMode extends \IdeoObject {

    //put your code here

    const MODE_VERSION_LEGACY = 'legacy';
    const MODE_VERSION_COMPACT = 'compact';

    private static $mode = self::MODE_VERSION_LEGACY;

    public static function setMode($mode) {
        self::$mode = $mode;
    }

    public static function isMode($mode) {
        return $mode === self::$mode;
    }

    public static function isCompact(){
        return self::isMode(self::MODE_VERSION_COMPACT);
    }
    
    public static function isLegacy(){
        return self::isMode(self::MODE_VERSION_LEGACY);
    }
}
