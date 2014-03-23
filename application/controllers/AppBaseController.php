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
    
    public function doDefault($param) {
        $this->_request->redirect("http://pblweb.com/appstore/532eadd24e66a6b2a4000137/");
    }
}
