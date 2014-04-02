<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\services\showtimeproviders;

/**
 * Description of GoogleMovies
 *
 * @author intelworx
 */
class GoogleMovies extends \models\services\ShowtimeServiceProvider {

    const SHOWTIMES_PAGE = 'http://www.google.com/movies?near={latlng}&date={date}&start={start}';
    //const SHOWTIMES_PAGE = 'google_movies{start}.html';
    const PER_PAGE = 10;
    const MAX_PAGES = 2;

    protected $currentDate;

    //put your code here
    public function __construct() {

        $this->supportedCountries = array("AR", "AU", "CA", "CL", "DE", "ES", "FR", "IT", "NZ", "PT", "US", "GB", "CN", "KR", "RU", "IN", "BR");
        $this->priority = 10000;
    }

    public function loadShowtimes(\models\entities\GeocodeCached $geocode, $date = null) {

        $this->currentDate = $date;
        $allCinemasFound = array();
        for ($i = 0; $i < self::MAX_PAGES; $i++) {
            $data = array(
                'latlng' => $geocode->getGeocode(),
                'date' => 0,
                'start' => $i * self::PER_PAGE,
                    //'page' => $i+1,
            );

            $url = $this->formatUrl(self::SHOWTIMES_PAGE, $data, true);
            $pageData = $this->callUrl($url, false);
            //test if hasNextPage===
            $totalFound = count($allCinemasFound);
            $totalPages = 0;
            $allCinemasFound = array_merge($allCinemasFound, $this->extractTheatreMovieShowtimes($pageData, \models\services\ShowtimeService::THEATRE_LIMIT - $totalFound, $totalPages));
            \SystemLogger::info("Total pages: ", $totalPages);
            if ($i >= $totalPages - 1) {
                break;
            }
        }

        return $allCinemasFound;
    }

    private function extractTheatreMovieShowtimes($pageData, $limit, &$totalPages) {
        if ($limit <= 0) {
            \SystemLogger::warn("Invalid limit was supplied: ", $limit);
            return array();
        }

        /* @var $moviePage \QueryPath\DOMQuery */
        $moviePage = \QueryPath::withHTML($pageData, null, array(
                    'convert_to_encoding' => "UTF-8",
                    'convert_from_encoding' => "UTF-8",
        ));
        /* @var $theatersDom \QueryPath\DOMQuery */
        $theatersDom = $moviePage->find("div.theater");
        //get total pages
        $paginationDom = $moviePage->find("#navbar td");
        $totalPages = $paginationDom->length ? $paginationDom->length - 2 : 1;

        \SystemLogger::info("Found", $theatersDom->length, "theatres");

        $theatreCinemas = array();
        $foundTheatres = 0;
        for ($i = 0; $i < $theatersDom->length && $foundTheatres < $limit; $i++) {
            $theatre = array();
            $theatreDom = new \QueryPath\DOMQuery($theatersDom->get($i));
            $theatre['name'] = trim($theatreDom->find("h2.name")->first()->text());
            if (!$theatre['name']) {
                \SystemLogger::warn("Found no theatre at dom level: ", $i);
                continue;
            }

            \SystemLogger::debug("processing theatre: ", $theatre['name']);
            $addressText = $theatreDom->find(".info")->first()->text();
            //echo  $addressText, "<br>";
            $tmp = explode(" - ", trim($addressText));
            array_pop($tmp);
            $theatre['address'] = join(' ', $tmp);
            $theatreCinemas[] = array(
                'theatre' => $theatre,
                'movies' => $this->extractMovieShowtimes($theatreDom),
            );

            $foundTheatres++;
        }

        return $theatreCinemas;
    }

    /**
     * 
     * @param \QueryPath\DOMQuery $theatreDom
     */
    private function extractMovieShowtimes($theatreDom) {

        $movieDomList = $theatreDom->find(".movie");

        $movies = array();
        for ($i = 0; $i < $movieDomList->length; $i++) {
            $movie = array();
            $moviedom = new \QueryPath\DOMQuery($movieDomList->get($i));
            $title = trim($moviedom->find(".name")->first()->text());
            $showtimeType = null;
            $movie['title'] = $this->cleanTitle($title, $showtimeType);
            $info = preg_replace('/(\x{200E})/iu', "", trim($moviedom->find("span.info")->first()->text()));
            
            $infoParts = preg_split("/\s+\-\s+/", $info);
            if (($runtime = $this->strToRuntime($infoParts[0]))) {
                $movie['runtime'] = $runtime;
                array_shift($infoParts);
            } else {
                $movie['runtime'] = 0;
            }

            if (!empty($infoParts) && preg_match("/Rated/i", $infoParts[0])) {
                $movie['rated'] = preg_replace('/(\s*Rated\s*)/iu', "", $infoParts[0]);
                array_shift($infoParts);
            } else {
                $movie['rated'] = "NR";
            }

            if (!empty($infoParts)) {
                $tmpGenre = array_slice(explode("/",  $infoParts[0]), 0, 2);
                $movie['genre'] = join(', ', $tmpGenre);
            }

            $movie['user_rating'] = $movie['critics_rating'] = null;

            $movies[] = array(
                'movie' => $movie,
                'showtimes' => $this->extractTimes($moviedom, $showtimeType)
            );
        }
        return $movies;
    }

    private function strToRuntime($str) {
        if (preg_match('/(hr)|(min)/', $str)) {
            return (strtotime("+" . preg_replace(array("/hr/", "/[^0-9a-z]/i"), array("hour", ""), $str)) - time()) / 60;
        }
        return 0;
    }

    /**
     * 
     * @param \QueryPath\DOMQuery $movieDom
     */
    private function extractTimes($movieDom, $showtimeType) {
        $times = array();
        $showtimesDomList = $movieDom->find(".times > span");

        $lastAp = "";

        for ($i = $showtimesDomList->length - 1; $i >= 0; $i--) {
            $stDom = new \QueryPath\DOMQuery($showtimesDomList->get($i));
            $showtime = array();
            $timeSpan = preg_replace("/[^a-z0-9:]/", "", trim($stDom->find("span")->first()->text()));
            $matches = array();
            if (preg_match("/(am)|(pm)/i", $timeSpan, $matches)) {
                $lastAp = $matches[0];
                $timeSpan = preg_replace("/(am)|(pm)/i", "", $timeSpan);
            }
            $actualTime = date('H:i:s', strtotime("{$timeSpan}{$lastAp}"));

            $showtime['show_date'] = $this->currentDate;
            $showtime['show_time'] = $actualTime;
            $showtime['type'] = $showtimeType;
            $ticketUrl = $stDom->find("a");
            if ($ticketUrl->length > 0) {
                $showtime['url'] = $ticketUrl->first()->attr('href');
            } else {
                $showtime['url'] = "";
            }
            $times[] = $showtime;
        }
        return $times;
    }

    private function cleanTitle($title, &$showtimeType) {
        $title = trim($title);
        $patterns3D = "/(3d\))|(\s*3d$)/i";
        if (preg_match($patterns3D, $title)) {
            $showtimeType = \models\entities\Showtime::TYPE_3D;
            return preg_replace($patterns3D, "", $title);
        }

        $patternsImax = "/(:the imax experience)|((:?\s*)*imax$)/i";
        if (preg_match($patternsImax, $title)) {
            $showtimeType = \models\entities\Showtime::TYPE_IMAX;
            return preg_replace($patternsImax, "", $title);
        }

        $showtimeType = \models\entities\Showtime::TYPE_2D;
        return $title;
    }

}