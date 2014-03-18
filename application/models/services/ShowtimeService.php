<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\services;

use ComparableObjectSorter;
use DbTableFunction;
use DirectoryIterator;
use IdeoObject;
use InvalidArgumentException;
use models\entities\GeocodeCached;
use models\entities\Movie;
use models\entities\Showtime;
use models\entities\Theatre;
use models\entities\TheatreNearby;
use models\entitymanagers\StandardEntityManager;
use SystemLogger;

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

    const PROVIDERS_DIR = 'locationproviders';

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

    /**
     *
     * @var ShowtimeServiceProvider[]
     */
    protected $serviceProviderList = array();

    private function __construct() {
        //load providers
        $serviceProvidersDir = __DIR__ . DS . 'showtimeproviders';
        $directoryIterator = new DirectoryIterator($serviceProvidersDir);
        while ($directoryIterator->valid()) {
            if ($directoryIterator->isFile() && $directoryIterator->isReadable()) {
                $className = __NAMESPACE__ . '\\showtimeproviders\\' . explode('.', $directoryIterator->getBasename())[0];
                if (class_exists($className)) {
                    $this->serviceProviderList[] = new $className();
                }
            }
            $directoryIterator->next();
        }

        if (!count($this->serviceProviderList)) {
            throw new InvalidArgumentException("There are no service providers defined for showtimes.");
        }

        ComparableObjectSorter::sort($this->serviceProviderList, false, true);

        //initialize showtime manager
        $this->showtimeManager = Showtime::manager();
        $this->theatreManager = Theatre::manager();
        $this->theatreNearByManager = TheatreNearby::manager();
    }

    public function dataLoaded(GeocodeCached $locationInfo, $date = null) {
        $queryWhere = $locationInfo->getQueryWhere();
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

    /**
     * Fetches data for the showtimes for the soecified info.
     * @param GeocodeCached $locationInfo
     * @param type $date
     * @param type $forceReload
     * @return boolean
     */
    public function loadData(GeocodeCached $locationInfo, $date = null, $forceReload = false) {
        if (!$forceReload && $this->dataLoaded($locationInfo, $date)) {
            return true;
        }

        $results = array();
        foreach ($this->serviceProviderList as $serviceProvider) {
            if ($serviceProvider->supports($locationInfo)) {
                $results = $serviceProvider->loadShowtimes($locationInfo, $date);
                if (!empty($results)) {
                    //cache and save...
                    return $this->cacheResult($results, $locationInfo);
                }
            }
        }

        return false;
    }

    protected function cacheResult($results, $locationInfo) {
        //throw new Exception("Work in progress");
        $return = 0;
        foreach ($results as $theatreMovieShowtime) {
            $theatre = Theatre::getOrCreate($theatreMovieShowtime['theatre'], $locationInfo);
            if ($theatre) {
                foreach ($theatreMovieShowtime['movies'] as $movieShowtimeData) {
                    $movie = Movie::getOrCreate($movieShowtimeData['movie']);
                    if ($movie) {
                        $return += $this->cacheShowtimes($theatre, $movie, $movieShowtimeData['showtimes']);
                    }
                }
            } else {
                SystemLogger::warn("Could not create theatre with data: ", $theatreMovieShowtime['theatre']);
            }
        }
        return $results;
    }

    protected function cacheShowtimes(Theatre $theatre, Movie $movie, $showtimes) {
        foreach ($showtimes as $k => $showtime) {
            $showtimes[$k]['theatre_id'] = $theatre->id;
            $showtimes[$k]['movie_id'] = $movie->id;
        }

        return Showtime::table()
                        ->insert($showtimes, true, true);
    }

    /**
     * 
     * @return self
     */
    public static function instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

}
