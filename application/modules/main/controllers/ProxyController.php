<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace main\controllers;

/**
 * Description of ProxyController
 *
 * @author intelWorX
 */
class ProxyController extends \controllers\AppBaseController {

    /**
     *
     * @var \models\entities\UserDevice
     */
    protected $userDevice;
    protected $skipAuths = array('register');
    protected $requestId;

    /**
     *
     * @var \models\entities\GeocodeCached
     */
    protected $geocode;

    const ALLOWED_LAG = 300;

    /**
     *
     * @var \main\models\Response
     */
    protected $response;

    public function __construct($req) {
        $this->response = new \main\models\Response();
        $this->_request->addHook(new \main\models\RequestLogger());
        parent::__construct($req);
    }

    protected function _initCredentials() {
        $token = $this->_request->getQueryParam('token');
        $this->userDevice = \models\entitymanagers\UserDeviceManager::validate($token, $this->requestId);
        $action = $this->_request->getAction();
        if (!in_array($action, $this->skipAuths)) {
            $this->response->forbidden();
            $this->response->addError(new \main\models\ApiError("FORBIDDEN", "Access denied"));
            $this->display();
            exit;
        }

        if ($this->userDevice) {
            \main\models\RequestLogger::addRecord(array(
                'request_id' => $this->requestId,
                'req_type' => $this->_request->getAction(),
                'user_device_id' => $this->userDevice->id,
            ));
        }
    }

    protected function _initGeocode() {
        $locationService = \models\services\LocationService::instance();
        if ($this->_request->hasQueryParam('latlng')) {
            $latLng = $this->_request->getQueryParam('latlng');
            $this->geocode = $locationService->lookUp($latLng);
        }
        
        if(!$this->geocode && ($this->_request->hasQueryParam('city') || $this->_request->hasQueryParam('country') || $this->_request->hasQueryParam('postalCode'))){
            $countryIso = $this->_request->getQueryParam('country');
            $city = $this->_request->getQueryParam('city');
            $postalCode = $this->_request->getQueryParam('postalCode');
            $this->geocode = $locationService->postalCodeLookup($postalCode, $countryIso, $city);
        }
    }

    public function doDefault() {
        $this->_forward('register');
    }

    public function doRegister() {
        $this->_enforcePOST();
        $timeInt = (int) substr($this->requestId, 0, strlen($this->requestId) - 3);
        $diff = abs($timeInt - time());
        if (!$this->userDevice) {
            if ($diff > self::ALLOWED_LAG) {
                $this->response->badRequest();
                $this->response->addError(new \main\models\ApiError(400, "Validation error"));
            } else {
                $device_uuid = $this->_request->getPostData('device_uuid');
                $userDevice = \models\entitymanagers\UserDeviceManager::register($device_uuid);
                if (!$userDevice) {
                    $this->response->addError(new \main\models\ApiError(400, "Error creating entry"));
                } else {
                    $this->userDevice = $userDevice;
                }
            }
        }

        if ($this->userDevice) {
            //set to body
            $this->response->setResult(array('device' => $this->userDevice->toArray(0)));
        }
    }

    public function doTheatres() {
        
    }

    public function doTheatreMovies() {
        
    }

    public function doTheatreMovieShowtimes() {
        
    }

    public function doMovies() {
        
    }

    public function doMovieTheatres() {
        
    }

    protected function _enforceMethod($method = 'GET') {
        if (strcasecmp($_SERVER['REQUEST_METHOD'], $method) !== 0) {
            $this->response->addError(new \main\models\ApiError(400, "Invalid request method"));
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

    public function display() {
        $this->response->setResult($this->result);
        $this->response->output();
    }

}
