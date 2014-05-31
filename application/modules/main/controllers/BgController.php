<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace main\controllers;

use controllers\AppBaseController;
use models\LocationUpdater;
use models\services\ShowtimeService;

/**
 * Description of BgController
 *
 * @author JosephT
 */
class BgController extends AppBaseController {

    //put your code here

    public function doClean() {
        $deleted = ShowtimeService::cleanShowdates();
        echo "Showtimes deleted: ", $deleted, "\n";
        $cleaned = ShowtimeService::cleanPbis();
        echo "PBI deleted: ", $cleaned, "\n";
        exit;
    }

    public function doDistance() {
        $limit = $this->_request->getQueryParam('limit', true, 100);
        $updated = LocationUpdater::update($limit);
        echo "Total Successful = ", $updated, "\n";
        exit;
    }

    protected function _initBg() {
        if ($this->_request->getQueryParam('skip') != 200) {
            die('Forbidden');
        }
        
        set_time_limit(0);
        ignore_user_abort(1);
    }

}
