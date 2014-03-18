<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\entitymanagers;

/**
 * Description of UserDeviceManager
 *
 * @author intelWorX
 */
class UserDeviceManager extends AppEntityManager {

    public static function register($device_uuid) {
        $parts = explode(" ", microtime());

        $data = array(
            'id' => $parts[1] . round($parts[0] * 1000),
            'secret_key' => \Utilities::getRandomCode(64),
            'device_uuid' => $device_uuid
        );

        $inserted = static::instance()->createEntity($data)
                ->save();

        return $inserted ? static::instance()->getEntity($data['id']) : null;
    }

    /**
     * 
     * @param string $token format requestId|deviceId|sign=sha1(requestId.deviceId.secretKey)
     * @return \models\entities\UserDevice current device represented by token.
     */
    public static function validate($token, &$requestId, $verifySign = true) {
        list($requestId, $deviceId, $sign) = explode('|', $token);
        if (!($requestId && $deviceId && $sign)) {
            return null;
        }

        $instance = static::instance();
        $userDevice = $instance->getEntity($deviceId);
        if (!$userDevice) {
            \SystemLogger::warn("Invalid user device ID: [{$deviceId}]");
            return null;
        }

        if ($verifySign) {
            if ($sign != self::sign($requestId, $deviceId, $userDevice->secret_key)) {
                \SystemLogger::warn("Signature not valid: ", $sign);
                return null;
            }

            $tableWhere = (new \DbTableWhere())->where('user_device_id', $userDevice->id)
                    ->where('request_id', $requestId)
                    ->setLimitAndOffset(1);
            
            if (count(UserDeviceReqManager::instance()->getEntitiesWhere($tableWhere))) {
                \SystemLogger::warn("duplicate request id: ", $requestId);
                return null;
            }
        }
        
        return $userDevice;
    }

    public static function sign($requestId, $deviceId, $secretKey) {
        return sha1($requestId . $deviceId . $secretKey);
    }

}
