<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace main\models;

/**
 * Description of ResponseFormatterFactory
 *
 * @author intelWorX
 */
class ResponseFormatterFactory extends \IdeoObject {

    //put your code here

    /**
     * 
     * @param string $format, the response type
     * @return \api\models\ResponseFormatter
     * 
     */
    public static function getFormatter($format = Response::FORMAT_JSON) {
        $class = __NAMESPACE__ . "\\ResponseFormatter" . strtoupper($format);
        if (class_exists($class)) {
            return new $class();
        } else {
            throwException(new \Exception("Could not find Response formatter for {$format}", null, null));
        }
    }

}
