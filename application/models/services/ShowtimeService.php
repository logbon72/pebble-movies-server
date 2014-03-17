<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\services;

use DbTableFunction;
use DbTableWhere;
use IdeoObject;
use models\entities\GeocodeCached;
use models\entities\Showtime;
use models\entities\Theatre;
use models\entities\TheatreNearby;
use models\entitymanagers\StandardEntityManager;

/**
 * Description of ShowtimeService
 *
 * @author intelWorX
 */
class ShowtimeService extends IdeoObject {
    //put your code here

    /**
     *
     * @var ShowtimeService
     */
    private static $instance;

    /**
     *
     * @var StandardEntityManager
     */
    protected $showtimeManager;

    /**
     *
     * @var StandardEntityManager
     */
    protected $theatreManager;

    /**
     *
     * @var StandardEntityManager
     */
    protected $theatreNearByManager;

    private function __construct() {
        //load providers
        //initialize showtime manager
        $this->showtimeManager = Showtime::manager();
        $this->theatreManager = Theatre::manager();
        $this->theatreNearByManager = TheatreNearby::manager();
    }

    public function dataLoaded(GeocodeCached $locationInfo, $date = null) {
        $queryWhere = new DbTableWhere();
        $queryWhere->where('country_iso', $locationInfo->country_iso);
        if ($locationInfo->postal_code) {
            $queryWhere->where('postal_code', $locationInfo->postal_code);
        } else {
            $queryWhere->where('city', $locationInfo->city);
        }

        if ($date) {
            $queryWhere->where('s.show_date', $date);
        }

        return TheatreNearby::table()
                        ->selectFrom(array(new DbTableFunction("count(s.id) AS c")), 'tn')
                        ->innerJoin(array('t' => Theatre::table()), 't.id = tn.theatre_id')
                        ->innerJoin(array('s' => Showtime::table()), 's.theatre_id = t.id')
                        ->where($queryWhere)
                        ->query(true) > 0
        ;
    }

    public static function instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

}
