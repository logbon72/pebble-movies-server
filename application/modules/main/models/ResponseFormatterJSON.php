<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace main\models;
/**
 * Description of ResponseFormatterJSON
 *
 * @author intelWorX
 */
class ResponseFormatterJSON extends ResponseFormatter {
    
    public function format(array $result, $pretty=false) {
        $json = json_encode($result);
        return $pretty ? JSONFormatter::format($json) : $json;
    }

}
