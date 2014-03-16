<?php

namespace main\models;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ResponseFormatter
 *
 * @author intelWorX
 */
abstract class ResponseFormatter extends \IdeoObject {

    abstract public function format(array $result, $pretty=false);
}
