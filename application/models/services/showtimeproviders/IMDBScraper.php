<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\services\showtimeproviders;

use models\entities\GeocodeCached;
use models\entities\Showtime;
use models\services\ShowtimeService;
use models\services\ShowtimeServiceProvider;
use QueryPath;
use QueryPath\DOMQuery;

libxml_use_internal_errors(true);

/**
 * Description of IMDBScraper
 *
 * @author intelWorX
 */
class IMDBScraper extends ShowtimeServiceProvider
{

    const SHOWTIMES_PAGE = 'http://www.imdb.com/showtimes/{countryIso}/{postalCode}/{date}';
    private $urlTemplate = self::SHOWTIMES_PAGE;
    //const THEATRE_LIMIT = 15;
    const MAX_RATING = 10.0;
    const MAX_METASCORE = 100.0;

    protected $currentDate;

    protected $supportedCountries = [
        /*"AR", */
        "AU",
        /*"CA",*/
        "CL",
        "DE",
        "ES",
        "FR",
        "IT",
        "MX",
        "NZ",
        "PT",
        "US",
        "GB"
    ];

    /**
     * @return string
     */
    public function getUrlTemplate()
    {
        return $this->urlTemplate;
    }

    /**
     * @param string $urlTemplate
     * @return $this
     */
    public function setUrlTemplate($urlTemplate)
    {
        $this->urlTemplate = $urlTemplate;
        return $this;
    }


    public function loadShowtimes(GeocodeCached $geocode, $currentDate = null, $dateOffset = 0)
    {
        \SystemLogger::debug('SCRAPING started');
        $data = [
            'countryIso' => $geocode->country_iso,
            'date' => \Utilities::dateFromOffset($currentDate ?: date('Y-m-d'), $dateOffset),
            'postalCode' => urlencode($geocode->postal_code),
        ];


        $pageData = $this->callUrl($this->formatUrl($this->urlTemplate, $data, true));
        //$pageData = file_get_contents(__DIR__ . DS . "showtimes.html");
        //file_put_contents("data_".microtime(true).".html", $pageData);
        $this->currentDate = $data['date'];
        return $this->extractShowtimes($pageData);
    }

    protected function extractShowtimes($pageData)
    {
        $startTime = microtime(true);
        \SystemLogger::debug('Extraction of page started, total length = ', strlen($pageData));
        \SystemLogger::debug('Loading into QueryPath');
        $imdbPage = QueryPath::withHTML($pageData);
        \SystemLogger::debug('Query Path done loading...');

        \SystemLogger::debug(__CLASS__, "Extracting theatres...");
        //$cinemasList = $imdbPage->find("#cinemas-at-list .list_item.odd, #cinemas-at-list .list_item.even");
        $cinemasList = $imdbPage->find("#cinemas-at-list > .list_item");
        \SystemLogger::debug(__CLASS__, "Found: ", $cinemasList->count(), "cinemas");
        $theatreMovieShowtimes = array();

        if ($cinemasList) {
            for ($i = 0; ($i < ShowtimeService::THEATRE_LIMIT && $i < $cinemasList->count()); $i++) {
                \SystemLogger::debug('Processing theatre at position: ', $i);
                /* @var $cinemaDiv DOMQuery */
                $cinemaDiv = new DOMQuery($cinemasList->get($i));
                $theatreData = array();
                $theatreTitle = $cinemaDiv->find('h3')->first();
                \SystemLogger::info("{$i}. Theatre: ", $theatreTitle->text());
                if (!$theatreTitle) {
                    \SystemLogger::debug(__CLASS__, "No theatre found");
                    continue;
                }

                $theatreData['name'] = trim($theatreTitle->text());

                $addressSpanTmp = $cinemaDiv->find('.address div');

                $addressSpan = $addressSpanTmp ? preg_replace('/\s+/', ' ', $addressSpanTmp->text()) : "";
                $theatreData['address'] = trim(explode('|', $addressSpan)[0]);
                $movieDomList = $cinemaDiv->find('.list_item');
                \SystemLogger::info(__CLASS__, "Number of Movies = ", $movieDomList->count());
                if (!count($movieDomList)) {
                    \SystemLogger::debug(__CLASS__, "No movies found");
                    continue;
                }

                $theatreMovieShowtimes[] = array(
                    'theatre' => $theatreData,
                    'movies' => $this->extractMovies($movieDomList),
                );
                \SystemLogger::debug('--Theatre extraction completed---');
            }
        }

        \SystemLogger::debug('Showtimes extraction completed in :', (microtime(true) - $startTime));
        //var_dump($theatreMovieShowtimes);exit;
        return $theatreMovieShowtimes;
    }

    private function extractMovies(DOMQuery $movieDomList)
    {
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
     * @return array
     */
    private function extractTimes($showtimesDomList, $movieDom)
    {

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
                $showtimesArr = preg_split('/(\|\s*)+/', $showtimes);
                $lastAp = 'am';
                foreach ($showtimesArr as $timeThing) {
                    if (preg_match('/(am)|(pm)/i', $timeThing)) {
                        $tmp = explode(' ', $timeThing);
                        $lastAp = $tmp[1] ?: $lastAp;
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

    private function getShowtimeType($text)
    {
        if (preg_match('/imax/i', $text)) {
            return Showtime::TYPE_IMAX;
        } elseif (preg_match('/3D/i', $text)) {
            return Showtime::TYPE_3D;
        } else {
            return Showtime::TYPE_2D;
        }
    }

}
