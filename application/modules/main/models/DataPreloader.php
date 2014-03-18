<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace main\models;

/**
 * Description of DataPreloader
 *
 * @author intelWorX
 */
class DataPreloader extends \ClientRequestHook {
    protected $startTime;
    public function preDispatch(\ClientHttpRequest $request, \BaseController $controller) {
        $this->startTime = microtime(true);
    }
    //put your code here
    public function shutdown(\ClientHttpRequest $request, \main\controllers\ProxyController $controller) {
        flush();
        set_time_limit(0);
        ob_start();
        $locationInfo = $controller->getLocationInfo();
        $showtimeService = \models\services\ShowtimeService::instance();
        $status = false;
        if($locationInfo){
            $status = $showtimeService->loadData($locationInfo, $controller->getCurrentDate());
        }
        ob_end_clean();
        //\SystemLogger::info("Total Run time");
        return $status ? 1 : 0;
    }
}
