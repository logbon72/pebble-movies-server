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
    protected $currentDate;
    protected $result = array();

    const ALLOWED_LAG = 300;

    /**
     *
     * @var \main\models\Response
     */
    protected $response;

    /**
     *
     * @var \models\services\ShowtimeService
     */
    protected $showtimeService;

    /**
     * 
     * @param \ClientHttpRequest $req
     */
    public function __construct($req) {
        $this->response = new \main\models\Response();
        $req->addHook(new \main\models\RequestLogger(), 1000)
        //->addHook(new \main\models\DataPreloader())
        ;
        parent::__construct($req);
        $this->showtimeService = \models\services\ShowtimeService::instance();
    }

    protected function _initCredentials() {
        $token = $this->_request->getQueryParam('token');
        $this->userDevice = \models\entitymanagers\UserDeviceManager::validate($token, $this->requestId);
        $action = $this->_request->getAction();
        $testMode = /* !\Application::currentInstance()->isProd() && */ $this->_request->getQueryParam('skip') == 1;
        if (!$this->userDevice && !$testMode && !in_array($action, $this->skipAuths)) {
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

        if (!$this->geocode && ($this->_request->hasQueryParam('city') || $this->_request->hasQueryParam('country') || $this->_request->hasQueryParam('postalCode'))) {
            $countryIso = $this->_request->getQueryParam('country');
            $city = $this->_request->getQueryParam('city');
            $postalCode = $this->_request->getQueryParam('postalCode');
            $this->geocode = $locationService->postalCodeLookup($postalCode, $countryIso, $city);
        }

        $this->currentDate = $this->_request->getQueryParam('date') ? date('Y-m-d', strtotime($this->_request->getQueryParam('date'))) : date("Y-m-d");
    }

    public function doDefault() {
        $this->_forward('register');
    }

    public function doPreload() {
        if ($this->geocode) {
            $status = $this->showtimeService->loadData($this->geocode, $this->currentDate);
            \SystemLogger::addLog("PreloadStatus: ", $status);
            set_time_limit(0);
        }
        $this->result['status'] = $status;
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
            $this->result = array('device' => $this->userDevice->toArray(0));
        }
    }

    protected function _checkLocationInfo() {
        if (!$this->geocode) {
            $this->response->addError(new \main\models\ApiError("NO_GEO", "No geolocation info"));
            $this->display();
            exit;
        }
    }

    public function doTheatres() {
        $theatres = $this->showtimeService->getTheatres($this->geocode, $this->currentDate);
        $this->result['theatres'] = $theatres;
    }

    public function doTheatreMovies() {
        $theatreId = (int) $this->_request->getQueryParam('theatre_id');
        $this->result['theatre_movies'] = $theatreId ? $this->showtimeService->getMovies($this->geocode, $this->currentDate, $theatreId, true) : array();
    }

    public function doTheatreMovieShowtimes() {
        
    }

    public function doMovies() {
        $this->result['movies'] = $this->showtimeService->getMovies($this->geocode, $this->currentDate);
    }

    public function doMovieTheatres() {
        $movieId = (int) $this->_request->getQueryParam('movie_id');
        $this->result['movie_theatres'] = $movieId ? $this->showtimeService->getTheatres($this->geocode, $this->currentDate, $movieId, true) : array();
    }

    public function doTest() {
//        $imdbLoader = new \models\services\showtimeproviders\IMDBScraper();
//        $data = $imdbLoader->loadShowtimes($this->geocode);
//        var_dump($data);
//        exit;
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

    public function getLocationInfo() {
        return $this->geocode;
    }

    public function getCurrentDate() {
        return $this->currentDate;
    }

}
