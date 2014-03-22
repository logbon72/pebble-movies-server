<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace main\controllers;

/**
 * Description of LController
 *
 * @author intelworx
 */
class LController extends \controllers\AppBaseController {

    //put your code here

    public function __do($showtime_id) {
        if (($showtime_id = intval($showtime_id))) {
            $showtime = \models\entities\Showtime::manager()
                    ->getEntity($showtime_id);
            if ($showtime && $showtime->url) {
                $showtime->update(array('redirects' => new \DbTableFunction("redirects+1")), 'id');
                $this->_request->redirect($showtime->url);
            }
        }
        $this->_request->redirect('http://joseph.orilogbon.me');
    }

}
