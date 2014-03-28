<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\services\showtimeproviders;

use models\entities\GeocodeCached;
use models\entities\Showtime;
use models\services\ShowtimeServiceProvider;
use QueryPath;
use QueryPath\DOMQuery;
use SystemLogger;

/**
 * Description of IMDBScraper
 *
 * @author intelWorX
 */
class IMDBScraper extends ShowtimeServiceProvider {

    const SHOWTIMES_PAGE = 'http://www.imdb.com/showtimes/{countryIso}/{postalCode}/{date}';
    const THEATRE_LIMIT = 15;
    const MAX_RATING = 10.0;
    const MAX_METASCORE = 100.0;

    protected $currentDate;
    protected $supportedCountries = array("AR", "AU", "CA", "CL", "DE", "ES", "FR", "IT", "MX", "NZ", "PT", "US", "GB");

    public function loadShowtimes(GeocodeCached $geocode, $date = null) {

        $data = array(
            'countryIso' => $geocode->country_iso,
            'date' => $date ? : date('Y-m-d'),
            'postalCode' => urlencode($geocode->postal_code),
        );


        $pageData = $this->callUrl($this->formatUrl(self::SHOWTIMES_PAGE, $data, true), false);
        //$pageData = file_get_contents(__DIR__ . DS . "showtimes.htm");
        $this->currentDate = $data['date'];
        return $this->extractShowtimes($pageData);
    }

    protected function extractShowtimes($pageData) {
        $imdbPage = QueryPath::withHTML($pageData);
        SystemLogger::debug(__CLASS__, "Extracting theatres...");
        //$cinemasList = $imdbPage->find("#cinemas-at-list .list_item.odd, #cinemas-at-list .list_item.even");
        $cinemasList = $imdbPage->find("#cinemas-at-list .list_item.odd, #cinemas-at-list .list_item.even");
        SystemLogger::debug(__CLASS__, "Found: ", count((array) $cinemasList));
        $theatreMovieShowtimes = array();

        if ($cinemasList) {
            for ($i = 0; ($i <= self::THEATRE_LIMIT && $i < $cinemasList->count()); $i++) {
                /* @var $cinemaDiv DOMQuery */
                $cinemaDiv = new DOMQuery($cinemasList->get($i));
                $theatreData = array();
                $theatreTitle = $cinemaDiv->find('h3')->first();
                if (!$theatreTitle) {
                    SystemLogger::debug(__CLASS__, "No theatre found");
                    continue;
                }

                $theatreData['name'] = trim($theatreTitle->text());

                $addressSpanTmp = $cinemaDiv->find('.address div');

                $addressSpan = $addressSpanTmp ? preg_replace('/\s+/', ' ', $addressSpanTmp->text()) : "";
                $theatreData['address'] = trim(explode('|', $addressSpan)[0]);
                $movieDomList = $cinemaDiv->find('.list_item');
                SystemLogger::info(__CLASS__, "Number of Movie: ", count($movieDomList));
                if (!count($movieDomList)) {
                    SystemLogger::debug(__CLASS__, "No movies found");
                    continue;
                }

                $theatreMovieShowtimes[] = array(
                    'theatre' => $theatreData,
                    'movies' => $this->extractMovies($movieDomList),
                );
            }
        }
        return $theatreMovieShowtimes;
    }

    private function extractMovies(DOMQuery $movieDomList) {
        $movieResult = array();

        for ($i = 1; $i < $movieDomList->count(); $i++) {
            $movieDom = new DOMQuery($movieDomList->get($i));
            /* @var $movieDom DOMQuery */
            $movieData = array();
            $titledom = $movieDom->find('h4')->first();
            if (!$titledom) {
                continue;
            }


            $movieData['title'] = preg_replace('/\s+\(\d\d\d\d\)$/', '', trim($titledom->text()));

            $imgDomTmp = $movieDom->find('.image_sm img')->first();
            $movieData['poster_url'] = $imgDomTmp ? $imgDomTmp->attr('src') : '';

            $certImg = $movieDom->find('.certimage')->first();
            $movieData['rated'] = $certImg ? $certImg->attr('title') : '';

            $timeSpan = $movieDom->find('time')->first();
            $movieData['runtime'] = $timeSpan ? intval(trim($timeSpan->text())) : -1;

            $ratingDom = $movieDom->find('[itemprop=ratingValue]')->first();
            $movieData['user_rating'] = $ratingDom ? floatval($ratingDom->text()) / self::MAX_RATING : 0;


            $metaDom = $movieDom->find('span.nobr')->eq(1);
            $metaDomScoreTmp = explode("/", preg_replace('/[^0-9\/]+/', '', $metaDom->text()));
            $movieData['critic_rating'] = floatval($metaDomScoreTmp[0]) / self::MAX_METASCORE;

            //var_dump($movieDom->find('.showtimes')->count());exit;
            //echo (preg_replace("/\s+/", " ", $movieDom->text())), "<br/><br/>";
            //continue;
            $movieResult[] = array(
                'movie' => $movieData,
                'showtimes' => $this->extractTimes($movieDom->find('.showtimes'), $movieDom)
            );
        }
        return $movieResult;
    }

    /**
     * 
     * @param DOMQuery $showtimesDomList
     * @param DOMQuery $movieDom
     */
    private function extractTimes($showtimesDomList, $movieDom) {

        $index = 0;
        $times = array();
        foreach ($showtimesDomList as $showtimesDom) {
            $getTicketsLink = $showtimesDom->find('a')->first();
            $showtimeTypeDom = new DOMQuery($movieDom->find('h5.li_group')->get($index++));
            $showtimeType = $showtimeTypeDom ? $this->getShowtimeType(trim($showtimeTypeDom->text(), ' :\r\n')) : 'digital';
            if ($getTicketsLink->length > 0) {
                $timeList = explode('|', $getTicketsLink->attr('data-times'));
                $link = $getTicketsLink->attr('href');
                foreach ($timeList as $movieTime) {
                    $times[] = array(
                        'show_date' => $this->currentDate,
                        'show_time' => $movieTime,
                        'url' => $link ? "{$link}+{$movieTime}" : '',
                        'type' => $showtimeType,
                    );
                }
            } else {
                $showtimes = trim(preg_replace('/\s+/', ' ', $showtimesDom->text()));
                $showtimesArr = preg_split('/[\|\s]+/', $showtimes);
                $lastAp = 'am';
                foreach ($showtimesArr as $timeThing) {
                    if (preg_match('/am|pm/', $timeThing)) {
                        $tmp = explode(' ', $timeThing);
                        $lastAp = $tmp[1] ? : $lastAp;
                        $timeThing = $tmp[0];
                    }
                    $times[] = array(
                        'show_date' => $this->currentDate,
                        'show_time' => date('H:i:s', strtotime("{$timeThing} {$lastAp}")),
                        'type' => $showtimeType,
                        'url' => ''
                    );
                }
            }
        }
        return $times;
    }

    private function getShowtimeType($text) {
        if (preg_match('/imax/i', $text)) {
            return Showtime::TYPE_IMAX;
        } elseif (preg_match('/3D/i', $text)) {
            return Showtime::TYPE_3D;
        } else {
            return Showtime::TYPE_2D;
        }
    }

}
