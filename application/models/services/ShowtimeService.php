<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\services;

include_once LIB_DIR . 'phpqrcode/qrlib.php';
include_once LIB_DIR . 'bitlyapi/Bitly.php';

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
     * @var \Bitly
     */
    protected $bitly;
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
                ///debug_op($results, true);
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
        return $return;
    }

    protected function cacheShowtimes(Theatre $theatre, Movie $movie, $showtimes) {
        foreach ($showtimes as $k => $showtime) {
            $showtimes[$k]['theatre_id'] = $theatre->id;
            $showtimes[$k]['movie_id'] = $movie->id;
        }

        return Showtime::table()
                        ->insert($showtimes, true, true);
    }

    public function getShowtimes(GeocodeCached $locationInfo, $currentDate = null, $movie_id = null, $theatre_id = null) {
        if (!$this->loadData($locationInfo, $currentDate)) {
            return array();
        }

        $where = $locationInfo->getQueryWhere()
                ->where('s.show_date', $currentDate)
        ;

        if ($theatre_id) {
            $where->where('s.theatre_id', $theatre_id);
        }

        if ($movie_id) {
            $where->where('s.movie_id', $movie_id);
        }

        $idsIn = Showtime::table()->selectFrom(array('s.id'), 's')
                ->innerJoin(array('tn' => TheatreNearby::table()), "tn.theatre_id=s.theatre_id")
                ->where($where)
                ->generateSQL()
        ;
        //array('id', 'show_time', 'show_date', 'url');
        $showtimeWhere = (new \DbTableWhere())
                ->whereInSql('id', $idsIn)
                ->setOrderBy("theatre_id")
                ->setOrderBy("movie_id")
                ->setOrderBy("show_date")
                ->setOrderBy("show_time")
                ->setOrderBy("type");

//showtimes
        $showtimes = Showtime::manager()
                ->getEntitiesWhere($showtimeWhere);

        $showtimesResult = array();
        foreach ($showtimes as $showtime) {
            $key = "{$showtime->theatre_id}.{$showtime->movie_id}";
            if (!array_key_exists($key, $showtimesResult)) {
                $showtimesResult[$key] = array();
            }
            $showtimeArr = $showtime->toArray(0, 1, array('id', 'show_time', 'type'));
            $showtimeArr['link'] = strlen($showtime->url) > 0;
            $showtimesResult[$key][] = $showtimeArr;
        }

        return $showtimesResult;
    }

    public function getTheatres(GeocodeCached $locationInfo, $currentDate = null, $movie_id = null, $includeShowtimes = null, $includeMovieIds = false) {
        if (!$this->loadData($locationInfo, $currentDate)) {
            return array();
        }
        $where = $locationInfo->getQueryWhere()
                ->where('s.show_date', $currentDate)
                ->setOrderBy('distance_m', 'ASC')
                ->setGroupBy('t.id');

        if ($movie_id) {
            $where->where('s.movie_id', $movie_id);
        }

        $ids = Theatre::table()->selectFrom('t.id', 't')
                ->innerJoin(array('tn' => TheatreNearby::table()), 't.id = tn.theatre_id', array('tn.distance_m'))
                ->innerJoin(array('s' => Showtime::table()), 's.theatre_id = t.id', array(new DbTableFunction("GROUP_CONCAT(DISTINCT s.movie_id) AS movies")))
                ->where($where)
                ->query()
        ;

        $theatres = array();
        foreach ($ids as $idRow) {
            $theatre = $this->theatreManager->getEntity($idRow['id']);
            /* @var $theatre Theatre */
            if ($theatre) {
                $theatreArr = $theatre->toArray(Theatre::TO_ARRAY_MVA, 1, array('id', 'name', 'address'));
                $theatreArr['distance_m'] = $idRow['distance_m'];
                if ($includeShowtimes) {
                    $theatreArr['showtimes'] = [];
                    $showtimes = $theatre->getShowtimes($movie_id, $currentDate);
                    foreach ($showtimes as $showtime) {
                        $theatreArr['showtimes'][] = $showtime->toArray(0, 1, array('id', 'show_time', 'show_date', 'url', 'type'));
                    }
                }

                if ($includeMovieIds) {
                    $theatreArr['movies'] = explode(",", $idRow['movies']);
                }
                $theatres[] = $theatreArr;
            }
        }

        $this->_filterObject($theatres);
        return $theatres;
    }

    public function getMovies(GeocodeCached $locationInfo, $currentDate = null, $theatre_id = null, $includeShowtimes = false, $includeTheatreIds = false) {
        if (!$this->loadData($locationInfo, $currentDate)) {
            return array();
        }
        $where = $locationInfo->getQueryWhere()
                ->where('s.show_date', $currentDate)
        ;

        if ($theatre_id) {
            $where->where('s.theatre_id', $theatre_id);
        }

        $idsQuery = Movie::table()->selectFrom('m.id', 'm')
                ->innerJoin(array('s' => Showtime::table()), 's.movie_id = m.id')
                ->innerJoin(array('tn' => TheatreNearby::table()), 's.theatre_id = tn.theatre_id', array(new DbTableFunction("GROUP_CONCAT(DISTINCT tn.theatre_id ORDER BY distance_m ASC) as theatres")))
                ->where($where->setGroupBy("m.id"))
                //->generateSQL()
                ->query()
        ;

        $ids = array();
        $theatres = array();
        foreach ($idsQuery as $result) {
            $ids[] = $result['id'];
            $theatres[$result['id']] = explode(",", $result['theatres']);
        }

        $moviesWhere = (new \DbTableWhere())
                ->whereInArray('id', $ids)
                ->setOrderBy('title');

        $movieList = Movie::manager()
                ->getEntitiesWhere($moviesWhere);

        $movies = array();
        $movieFields = array('id', 'title', 'genre', 'user_rating', 'rated', 'critic_rating', 'runtime');
        Movie::setToArrayFields($movieFields);
        foreach ($movieList as $movieInList) {
            /* @var $movieInList Movie */
            $movie = $movieInList->toArray();
            //$this->_filterObject($movie);
            //add showtimes ?
            if ($includeShowtimes) {
                $movie['showtimes'] = array();
                foreach ($movieInList->getShowtimes($theatre_id, $currentDate) as $showtime) {
                    $movie['showtimes'][] = $showtime->toArray(0, 1, array('id', 'show_time', 'show_date', 'url', 'type'));
                }
            }
            

            if ($includeTheatreIds) {
                $movie['theatres'] = $theatres[$movieInList->id];
            }
            $movies[] = $movie;
        }

        $this->_filterObject($movies);
        return $movies;
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

    protected function _filterObject(&$object) {
        if (is_array($object)) {
            foreach ($object as &$val) {
                $this->_filterObject($val);
            }
        } else {
            if (preg_match('/^\d+\.\d+$/', $object)) {
                $object = doubleval($object);
            } elseif (is_numeric($object) && $object < PHP_INT_MAX) {
                $object = intval($object);
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $object)) {
                $object = date("c", strtotime($object));
            }
        }
    }

    public function checkCache($showtime_id) {
        if (!is_dir(CACHE_DIR) && !mkdir(CACHE_DIR, 0700)) {
            return null;
        }
        $fileName = CACHE_DIR . "/" . $this->cacheName($showtime_id);
        if (file_exists($fileName)) {
            return file_get_contents($fileName);
        }
        return null;
    }

    private function cacheName($id) {
        return CACHE_DIR . "/qrcode.{$id}.pbi";
    }

    public function getRawQrCode($showtime_id) {
        $showtime = $this->showtimeManager->getEntity($showtime_id);

        if ($showtime && $showtime->url) {
            $cached = $this->checkCache($showtime_id);
            if ($cached) {
                return $cached;
            }

            //$l = \SystemConfig::getInstance()->site['redirect_base'] . $showtime_id;
            $shorten = $this->getBitly()->shorten($showtime->url, 'j.mp');
            if($shorten){
                $l = $shorten['url'];
            }else{
                $l = \SystemConfig::getInstance()->site['redirect_base'] . $showtime_id;
            }
            
            $filename = tempnam(sys_get_temp_dir(), "qrcode_");
            //header("Content-Type: image/png");
            \QRcode::png($l, $filename, QR_ECLEVEL_L, 4, 1);
            $converter = new \ImageConverter($filename);
            $cacheFile = $this->cacheName($showtime_id);
            if ($converter->convertToPbi($cacheFile)) {
                return file_get_contents($cacheFile);
            }
        }
        return null;
    }

    public function getSupportedCountries() {
        $results = array();
        foreach ($this->serviceProviderList as $serviceProvider) {
            $supported = $serviceProvider->getSupportedCountries();
            if (empty($supported)) {
                return LookupResult::$ISO_TABLE;
            } else {
                $results = array_merge($results, $supported);
            }
        }

        sort($results);
        $countryTables = array();
        foreach ($results as $country) {
            $countryTables[$country] = LookupResult::$ISO_TABLE[$country];
        }
        return $countryTables;
    }

    public function getBitly() {
        if(!$this->bitly){
            $bitlyConfig = \SystemConfig::getInstance()->bitly;
            $this->bitly = new \Bitly($bitlyConfig['api_key'], $bitlyConfig['api_secret'], $bitlyConfig['token']);
        }
        return $this->bitly;
    }
}
