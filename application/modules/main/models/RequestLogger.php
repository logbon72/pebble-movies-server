<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace main\models;

/**
 * Description of RequestLogger
 *
 * @author intelWorX
 */
class RequestLogger extends \ClientRequestHook {

    //put your code here

    protected static $logRecords = array();

    public static function addRecord($record) {
        self::$logRecords[] = $record;
    }
    
    public function postDisplay(\ClientHttpRequest $request, \BaseController $controller) {
        parent::postDisplay($request, $controller);
        foreach (self::$logRecords as $i => $logRecord) {
            self::$logRecords[$i] = array_merge(array(
                'ip_address' => getRealIpAddress(),
                    ), $logRecord);
        }

        if (count(self::$logRecords)) {
            return \models\entitymanagers\UserDeviceReqManager::instance()
                            ->getEntityTable()
                            ->insert(self::$logRecords);
        }
        return false;
    }

}
