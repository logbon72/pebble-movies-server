<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\entities;

/**
 * Description of UserDevice
 *
 * @author intelWorX
 */
class UserDevice extends \Entity{
    //put your code here
    protected function initRelations() {
        $this->setOneToMany('requests', \models\entitymanagers\UserDeviceReqManager::instance(), 'created_on DESC');
    }
    
}
