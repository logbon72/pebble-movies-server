<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\services;

include_once LIB_DIR . 'phpqrcode/qrlib.php';
include_once LIB_DIR . 'bitlyapi/Bitly.php';

use Bitly;
use DbTableFunction;
use DbTableWhere;
use DirectoryIterator;
use IdeoObject;
use ImageConverter;
use InvalidArgumentException;
use main\models\ProxyMode;
use models\entities\GeocodeCached;
use models\entities\GeocodeLoaded;
use models\entities\Movie;
use models\entities\Showtime;
use models\entities\Theatre;
use models\entities\TheatreNearby;
use models\entitymanagers\StandardEntityManager;
use QRcode;
use SystemConfig;
use SystemLogger;
use Utilities;

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
     * @var Bitly
     */
    protected $bitly;

    /**
     *
     * @var ShowtimeServiceProvider[]
     */
    protected $serviceProviderList = array();

    const THEATRE_LIMIT = 15;

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

        //ComparableObjectSorter::sort($this->serviceProviderList, false, true);
        shuffle($this->serviceProviderList);
        //initialize showtime manager
        $this->showtimeManager = Showtime::manager();
        $this->theatreManager = Theatre::manager();
        $this->theatreNearByManager = TheatreNearby::manager();
    }

    public function dataLoaded(GeocodeCached $locationInfo, $date = null) {
        $queryWhere = $locationInfo->getQueryWhere();
        if ($date) {
            $queryWhere->where('load_date', $date);
        }

        return GeocodeLoaded::manager()->getEntityWhere($queryWhere) !== null;
    }

    /**
     * Fetches data for the showtimes for the soecified info.
     * @param GeocodeCached $locationInfo
     * @param type $date
     * @param type $forceReload
     * @return boolean
     */
    public function loadData(GeocodeCached $locationInfo, $date = null, $forceReload = false, $dateOffset = 0) {
        if (!$date) {
            $date = date('Y-m-d');
        }

        $newDate = Utilities::dateFromOffset($date, $dateOffset);

        if (!$forceReload && $this->dataLoaded($locationInfo, $newDate)) {
            return true;
        }

        set_time_limit(0);
        $results = array();

        foreach ($this->serviceProviderList as $serviceProvider) {
            if ($serviceProvider->supports($locationInfo)) {
                $results = $serviceProvider->loadShowtimes($locationInfo, $date, $dateOffset);
                ///debug_op($results, true);
                if (!empty($results)) {
                    //cache and save...
                    return $this->cacheResult($results, $locationInfo, $newDate);
                }
            }
        }

        return false;
    }

    /**
     * 
     * @param array $results
     * @param GeocodeCached $locationInfo
     * @return int number of cached results
     */
    protected function cacheResult($results, $locationInfo, $date) {
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

        $locationInfo->setLastUpdated($date);

        return $return;
    }

    protected function cacheShowtimes(Theatre $theatre, Movie $movie, $showtimes) {
        if (empty($showtimes)) {
            return 0;
        }

        foreach ($showtimes as $k => $showtime) {
            $showtimes[$k]['theatre_id'] = $theatre->id;
            $showtimes[$k]['movie_id'] = $movie->id;
        }

        $queryWhere = (new DbTableWhere())
                ->where('show_date', $showtime['show_date'])
                ->where('show_time', $showtime['show_time'])
                ->where('theatre_id', $theatre->id)
                ->where('movie_id', $movie->id)
                ->where('type', $showtime['type'])
        ;

        if (Showtime::manager()->getEntityWhere($queryWhere)) {
            \SystemLogger::info("Show times have already been cached.");
            return 1;
        }

        try {
            $inserted = Showtime::table()
                    ->insert($showtimes, true, true);
        } catch (\Exception $e) {
            \SystemLogger::info("Could not save showtime, possible duplicate, error message: ", $e->getMessage());
            $inserted = 0;
        }

        return $inserted;
    }

    public function getShowtimes(GeocodeCached $locationInfo, $currentDate = null, $movie_id = null, $theatre_id = null, $dateOffset = 0) {
        if (!$this->loadData($locationInfo, $currentDate, false, $dateOffset)) {
            return array();
        }

        $currentDate = Utilities::dateFromOffset($currentDate, $dateOffset);
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
        $showtimeWhere = (new DbTableWhere())
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
            if (ProxyMode::isCompact()) {
                $showtimesResult[$key][] = $this->compactShowtime($showtimeArr);
            } else {
                $showtimesResult[$key][] = $showtimeArr;
            }
        }

        return $showtimesResult;
    }

    private function compactShowtime($showtimeArr, $valsOnly = true) {
        $compacted = array();
        $compacted['id'] = intval($showtimeArr['id']);
        $compacted['t'] = preg_replace('/:00$/', '', $showtimeArr['show_time']);
        $compacted['r'] = Showtime::compact($showtimeArr['type']);
        if ($showtimeArr['link']) {
            $compacted['l'] = 1;
        } else {
            if ($valsOnly) {
                $compacted['l'] = 0;
            }
        }
        return $valsOnly ? array_values($compacted) : $compacted;
    }

    public function getTheatres(GeocodeCached $locationInfo, $currentDate = null, $movie_id = null, $includeShowtimes = null, $includeMovieIds = false, $dateOffset = 0, &$theatreFields = array()) {
        if (!$this->loadData($locationInfo, $currentDate, false, $dateOffset)) {
            return array();
        }

        $currentDate = Utilities::dateFromOffset($currentDate, $dateOffset);
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
        $theatreFields = array('id', 'name', 'address', 'distance_m');
        foreach ($ids as $idRow) {
            $theatre = $this->theatreManager->getEntity($idRow['id']);
            /* @var $theatre Theatre */
            if ($theatre) {
                $theatreArr = $theatre->toArray(Theatre::TO_ARRAY_MVA, 1, $theatreFields);
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
                $theatres[] = ProxyMode::isCompact() ? $this->compactTheatre($theatreArr) : $theatreArr;
            }
        }
        if ($includeShowtimes) {
            $theatreFields[] = 'showtimes';
        }

        if ($includeMovieIds) {
            $theatreFields[] = 'movies';
        }

        $this->_filterObject($theatres);
        return $theatres;
    }

    private function compactTheatre($theatreArr) {
        if(!$theatreArr['distance_m']){
            $theatreArr['distance_m'] = -1;
        }
        $compacted = array_values($theatreArr);
        return $compacted;
    }

    public function getMovies(GeocodeCached $locationInfo, $currentDate = null, $theatre_id = null, $includeShowtimes = false, $includeTheatreIds = false, $dateOffset = 0, &$movieFields = array()) {
        if (!$this->loadData($locationInfo, $currentDate, false, $dateOffset)) {
            return array();
        }

        $currentDate = Utilities::dateFromOffset($currentDate, $dateOffset);

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

        $moviesWhere = (new DbTableWhere())
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

            $movies[] = ProxyMode::isCompact() ? $this->compactMovie($movie) : $movie;
        }
        if ($includeTheatreIds) {
            $movieFields[] = 'theatres';
        }
        $this->_filterObject($movies);
        return $movies;
    }

    private function compactMovie($movie) {
        $compact = array_values($movie);
//        foreach ($compact as $f => $v) {
//            if (is_null($v)) {
//                $compact[$f] = 0;
//            }
//        }
        return $compact;
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
            try {
                $shorten = $this->getBitly()->shorten($showtime->url, 'j.mp');
                if ($shorten) {
                    $l = $shorten['url'];
                } else {
                    $l = SystemConfig::getInstance()->site['redirect_base'] . $showtime_id;
                }

                $filename = tempnam(sys_get_temp_dir(), "qrcode_");
                //header("Content-Type: image/png");
                QRcode::png($l, $filename, QR_ECLEVEL_L, 4, 1);
                $converter = new ImageConverter($filename);
                $cacheFile = $this->cacheName($showtime_id);
                if ($converter->convertToPbi($cacheFile)) {
                    return file_get_contents($cacheFile);
                }
            } catch (\Exception $e) {
                \SystemLogger::error(get_class($e), $e->getTraceAsString());
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
        if (!$this->bitly) {
            $bitlyConfig = SystemConfig::getInstance()->bitly;
            $this->bitly = new Bitly($bitlyConfig['api_key'], $bitlyConfig['api_secret'], $bitlyConfig['token']);
        }
        return $this->bitly;
    }

    public static function cleanShowdates() {
        $staleDate = date('Y-m-d', strtotime("-3 days"));
        $deleted = Showtime::table()->delete("show_date <= '{$staleDate}'");
        \SystemLogger::info("Cleaned ", $deleted, "show dates");
        return $deleted;
    }

    public static function cleanPbis() {
        $staleDate = strtotime("-4 days");
        $files = glob(CACHE_DIR . "/*");
        $removed = 0;
        foreach ($files as $file) {
            \SystemLogger::info("Looking at: ", $file);
            if (filemtime($file) < $staleDate && unlink($file)) {
                \SystemLogger::info("\tRemoved file: ", $file);
                $removed++;
            }
        }
        return $removed;
    }

}
