<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\entities;

/**
 * Description of UserDeviceRequest
 *
 * @author intelWorX
 */
class UserDeviceReq extends \Entity{
    
    protected function initRelations() {
        $this->setManyToOne('user_device', \models\entitymanagers\UserDeviceManager::instance());
    }
}
