<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace main\models;

/**
 * Description of ApiError
 *
 * @author intelWorX
 */
class ApiError extends \IdeoObject {

    protected $code = -1;
    protected $message;

    public function __construct($code, $message = null) {
        $this->code = $code;
        $this->message = $message === null ? $this->messageLookUp($code) : $message;
    }

    private function messageLookUp($code) {
        return \Strings::getValue('api', "E_" . $code);
    }

    public function asArray() {
        return array(
            'code' => $this->code,
            'message' => $this->message,
        );
    }

    public function getCode(){
        return $this->code;
    }
    
    public function getMessage(){
        return $this->message;
    }
}
