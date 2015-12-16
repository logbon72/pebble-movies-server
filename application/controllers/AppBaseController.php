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
class AppBaseController extends \BaseController {

    public function doDefault() {
        $this->_request->redirect("http://pblweb.com/appstore/532eadd24e66a6b2a4000137/");
    }

    protected function _enforceMethod($method = 'GET') {
        if (strcasecmp($_SERVER['REQUEST_METHOD'], $method) !== 0) {
            $this->response->addError(new ApiError(400, "Invalid request method"));
            $this->response->output();
            exit;
        }
    }

    protected function _enforceGET() {
        $this->_enforceMethod('GET');
    }

    protected function _enforcePOST() {
        $this->_enforceMethod('POST');
    }

}
