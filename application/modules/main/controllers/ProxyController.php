<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace main\controllers;

use ClientHttpRequest;
use controllers\AppBaseController;
use main\models\ApiError;
use main\models\ProxyMode;
use main\models\RequestLogger;
use main\models\Response;
use models\entities\GeocodeCached;
use models\entities\UserDevice;
use models\entitymanagers\UserDeviceManager;
use models\services\LocationService;
use models\services\ShowtimeService;
use SystemConfig;
use SystemLogger;

/**
 * Description of ProxyController
 *
 * @author intelWorX
 */
class ProxyController extends AppBaseController {

    /**
     *
     * @var UserDevice
     */
    protected $userDevice;
    protected $skipAuths = array('register', 'settings', 'clean');
    protected $requestId;

    const DATE_BUG_VERSION = 20140401;
    const UPGRADE_COMPACT_VERSION = 20140528;
    const UPGRADE_FORCE_LOC = 20140528;

    /**
     *
     * @var GeocodeCached
     */
    protected $geocode;
    protected $currentDate;
    protected $dateOffset = 0;
    protected $result = array();

    const ALLOWED_LAG = 300;

    /**
     *
     * @var Response
     */
    protected $response;

    /**
     *
     * @var ShowtimeService
     */
    protected $showtimeService;
    protected $currentVersion;
    protected $userVersion;

    /**
     * 
     * 
     * @param ClientHttpRequest $req
     */
    public function __construct($req) {
        $this->response = new Response();
        $req->addHook(new RequestLogger(), 1000);
        $this->currentVersion = doubleval(SystemConfig::getInstance()->system['current_version']);
        $this->userVersion = doubleval($req->getQueryParam('version'));
        parent::__construct($req);
        $this->showtimeService = ShowtimeService::instance();
    }

    protected function _initCredentials() {
        $token = $this->_request->getQueryParam('token');
        $this->userDevice = UserDeviceManager::validate($token, $this->requestId);
        $action = $this->_request->getAction();
        $testMode = /* !\Application::currentInstance()->isProd() && */ $this->_request->getQueryParam('skip') == 1;
        if (!$this->userDevice && !$testMode && !in_array($action, $this->skipAuths)) {
            $this->response->forbidden();
            $this->response->addError(new ApiError("FORBIDDEN", "Access denied"));
            $this->display();
            exit;
        }

        if ($this->userDevice) {
            RequestLogger::addRecord(array(
                'request_id' => $this->requestId,
                'req_type' => $this->_request->getAction(),
                'user_device_id' => $this->userDevice->id,
            ));
        }
    }

    protected function _initGeocode() {
        $locationService = LocationService::instance();
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
    }

    protected function _initDate() {
        if ($this->_request->getQueryParam('date')) {
            //$userVersion = doubleval($this->_request->getQueryParam('version'));
            $dateTs = strtotime($this->_request->getQueryParam('date'));
            if ($this->userVersion > self::DATE_BUG_VERSION) {
                $this->currentDate = date('Y-m-d', $dateTs);
            } else {
                $dayComp = (int) explode("-", $this->_request->getQueryParam('date'))[2];
                if ($dayComp < 1) {//1 for monday, 7 for sunday
                    $dayComp = 7;
                }

                $actualDayOfWeek = date('N');
                //$dayComp = intval(date('d', $dateTs));
                if ($dayComp < 10) {
                    //$dayToday
                    if ($actualDayOfWeek > $dayComp) {
                        $str = $actualDayOfWeek < 7 ? "yesterday" : "tomorrow";
                    } else if ($actualDayOfWeek < $dayComp) {
                        $str = $dayComp < 7 ? "tomorrow" : "yesterday";
                    } else {
                        $str = "today";
                    }


                    $dateTs = strtotime($str);
                }
                $this->currentDate = date('Y-m-d', $dateTs);
            }
        } else {
            $this->currentDate = date("Y-m-d");
        }

        $this->dateOffset = intval($this->_request->getQueryParam('dateOffset')) ? : 0;
        if ($this->dateOffset < 0) {
            $this->dateOffset = 0;
        }
    }

    public function doDefault() {
        $this->_forward('register');
    }

    public function doPreload() {
        $version = $this->currentVersion;
        if ($this->geocode) {
            $status = $this->showtimeService->loadData($this->geocode, $this->currentDate, false, $this->dateOffset);
            SystemLogger::info("PreloadStatus: ", $status);
        }
        $this->result['status'] = $status;
        $this->result['version'] = $version;
    }

    public function doPreload11() {
        $this->doPreload();
        $data = array();
        if ($this->result['status']) {
            //load movies:
            $movieFields = array();
            $theatreFields = array();
            $data = array(
                'movies' => $this->showtimeService->getMovies($this->geocode, $this->currentDate, 0, false, true, $this->dateOffset, $movieFields),
                'theatres' => $this->showtimeService->getTheatres($this->geocode, $this->currentDate, 0, false, true, $this->dateOffset, $theatreFields),
                'showtimes' => $this->showtimeService->getShowtimes($this->geocode, $this->currentDate, 0, 0, $this->dateOffset),
                'showtime_fields' => array('id', 'time', 'type', 'link'),
                'movie_fields' => $movieFields,
                'theatre_fields' => $theatreFields,
            );
        }
        $this->result['data'] = $data;
    }

    public function doRegister() {
        $this->_enforcePOST();
        $timeInt = (int) substr($this->requestId, 0, strlen($this->requestId) - 3);
        $diff = abs($timeInt - time());
        if (!$this->userDevice) {
            if ($diff > self::ALLOWED_LAG) {
                $this->response->badRequest();
                $this->response->addError(new ApiError(400, "Validation error"));
            } else {
                $device_uuid = $this->_request->getPostData('device_uuid');
                $userDevice = UserDeviceManager::register($device_uuid);
                if (!$userDevice) {
                    $this->response->addError(new ApiError(400, "Error creating entry"));
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
            $this->response->addError(new ApiError("NO_GEO", "No geolocation info"));
            $this->display();
            exit;
        }
    }

    public function doTheatres() {
        $theatres = $this->geocode ? $this->showtimeService->getTheatres($this->geocode, $this->currentDate) : array();
        $this->result['theatres'] = $theatres;
    }

    public function doQr() {
        $showtime_id = (int) $this->_request->getQueryParam('showtime_id');
        header("Content-Type: application/octet-stream");
        $data = "";
        if ($showtime_id) {
            $data = $this->showtimeService->getRawQrCode($showtime_id);
        }
        die($data);
    }

    public function doSettings() {
        $this->_view->availableCountries = $this->showtimeService->getSupportedCountries();
        $this->_view->geocode = $this->geocode;
        $this->_view->hasUpdate = $this->currentVersion > $this->userVersion;
        $this->_view->showForceLocation = $this->userVersion >= self::UPGRADE_FORCE_LOC;
        parent::display();
        exit;
    }

    public function doTheatreMovies() {
        $theatreId = (int) $this->_request->getQueryParam('theatre_id');
        $this->result['theatre_movies'] = $theatreId && $this->geocode ? $this->showtimeService->getMovies($this->geocode, $this->currentDate, $theatreId, true) : array();
    }

    public function doMovies() {
        $this->result['movies'] = $this->geocode ? $this->showtimeService->getMovies($this->geocode, $this->currentDate) : array();
    }

    public function doMovieTheatres() {
        $movieId = (int) $this->_request->getQueryParam('movie_id');

        $this->result['movie_theatres'] = $movieId && $this->geocode ? $this->showtimeService->getTheatres($this->geocode, $this->currentDate, $movieId, true) : array();
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

    public function doClean() {
        if ($this->_request->getQueryParam('skip') == 200) {
            $deleted = ShowtimeService::cleanShowdates();
            echo "Showtimes deleted: ", $deleted, "<br/>";
            $cleaned = ShowtimeService::cleanPbis();
            echo "PBI deleted: ", $cleaned, "<br/>";
        }
        exit;
    }

    protected function _initMode() {
        if ($this->userVersion && $this->userVersion >= self::UPGRADE_COMPACT_VERSION) {
            ProxyMode::setMode(ProxyMode::MODE_VERSION_COMPACT);
        } else {
            ProxyMode::setMode(ProxyMode::MODE_VERSION_LEGACY);
        }
    }

}
