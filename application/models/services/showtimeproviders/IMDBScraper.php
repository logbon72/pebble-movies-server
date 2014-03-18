<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\services\showtimeproviders;

require_once LIB_DIR . 'simple_html_dom.php';

/**
 * Description of IMDBScraper
 *
 * @author intelWorX
 */
class IMDBScraper extends \models\services\ShowtimeServiceProvider {

    const SHOWTIMES_PAGE = 'http://www.imdb.com/showtimes/{countryIso}/{postalCode}/{date}';
    const THEATRE_LIMIT = 15;
    const MAX_RATING = 10.0;
    const MAX_METASCORE = 100.0;

    protected $currentDate;

    public function loadShowtimes(\models\entities\GeocodeCached $geocode, $date = null) {
        $data = array(
            'countryIso' => $geocode->country_iso,
            'date' => $date ? : date('Y-m-d'),
            'postalCode' => $geocode->postal_code,
        );


        //$pageData = $this->callUrl($this->formatUrl(self::SHOWTIMES_PAGE, $data, true), false);
        $pageData = file_get_contents(__DIR__ . DS . "showtimes_imdb.htm");
        $this->currentDate = $data['date'];
        return $this->extractShowtimes($pageData);
    }

    protected function extractShowtimes($pageData) {
        //$('#cinemas-at-list > .list_item').each(function(i, el){console.log("Le:" + $(el).find('> .list_item').length)});

        $imdbPage = new \simple_html_dom($pageData);
        \SystemLogger::debug(__CLASS__, "Extracting theatres...");
        $cinemasList = $imdbPage->find("#cinemas-at-list .list_item.odd, #cinemas-at-list .list_item.even");
        \SystemLogger::debug(__CLASS__, "Found: ", count((array) $cinemasList));
        $foundTheatre = 0;
        $theatreMovieShowtimes = array();

        if ($cinemasList) {
            foreach ($cinemasList as $cinemaDiv) {
                /* @var $cinemaDiv \simple_html_dom_node */
                $theatreData = array();
                $theatreTitle = $cinemaDiv->find('h3', 0);
                if (!$theatreTitle) {
                    \SystemLogger::debug(__CLASS__, "No theatre found");
                    continue;
                }

                $theatreData['name'] = trim($theatreTitle->text());

                $addressSpanTmp = $cinemaDiv->find('.address div');

                $addressSpan = $addressSpanTmp ? preg_replace('/\s+/', ' ', $addressSpanTmp->text()) : "";
                $theatreData['address'] = trim(explode('|', $addressSpan)[0]);
                $movieDomList = $cinemaDiv->find('.list_item');
                \SystemLogger::info(__CLASS__, "Number of Movie: ", count($movieDomList));
                if (!$movieDomList || !count($movieDomList)) {
                    \SystemLogger::debug(__CLASS__, "No movies found");
                    continue;
                }

                $theatreMovieShowtimes[] = array(
                    'theatre' => $theatreData,
                    'movies' => $this->extractMovies($movieDomList),
                );

                $foundTheatre++;
                if ($foundTheatre >= self::THEATRE_LIMIT) {
                    break;
                }
            }
        }
        return $theatreMovieShowtimes;
    }

    private function extractMovies(\simple_html_dom_node $movieDomList) {
        $movieResult = array();
        foreach ($movieDomList as $movieDom) {
            /* @var $movieDom \simple_html_dom_node */
            $movieData = array();
            $titledom = $movieDom->find('h4', 0);
            if (!$titledom) {
                continue;
            }

            $movieData['title'] = preg_replace('/\s+\(\d\d\d\d\)$/', trim($titledom->text()), $movieDom);

            $imgDomTmp = $movieDom->find('.image_sm img', 0);
            $movieData['poster_url'] = $imgDomTmp ? $imgDomTmp->getAttribute('src') : '';

            $certImg = $movieDom->find('.certimage', 0);
            $movieData['rated'] = $certImg ? $certImg->getAttribute('title') : '';

            $timeSpan = $movieDom->find('time', 0);
            $movieData['runtime'] = $timeSpan ? intval(trim($timeSpan->text())) : -1;

            $ratingDom = $movieDom->find('[itemprop=ratingValue]', 0);
            $movieData['user_rating'] = $ratingDom ? floatval($ratingDom->text()) / self::MAX_RATING : 0;

            $metaDom = $movieDom->find('span.nobr', 1);
            if ($metaDom) {
                $metaDomScoreTmp = explode("/", preg_replace('/[^0-9\/]+/', '', $metaDom->text()));
                $movieData['critic_rating'] = floatval($metaDomScoreTmp) / self::MAX_METASCORE;
            }

            $showtimesDom = $movieDom->find('.showtimes');
            $movieResult[] = array(
                'movie' => $movieData,
                'showtimes' => $showtimesDom ? $this->extractTimes($showtimesDom, $movieDom) : array()
            );
        }

        return $movieResult;
    }

    /**
     * 
     * @param \simple_html_dom_node $showtimesDomList
     * @param \simple_html_dom_node $movieDom
     */
    private function extractTimes($showtimesDomList, $movieDom) {

        $index = 0;
        foreach ($showtimesDomList as $showtimesDom) {
            $times = array();
            $getTicketsLink = $showtimesDom->find('a', 0);
            $showtimeTypeDom = $movieDom->find('h5.li_group', $index++);
            $showtimeType = $showtimeTypeDom ? $this->getShowtimeType(trim($showtimeTypeDom->text(), ' :\r\n')) : 'digital';
            if ($getTicketsLink) {
                $timeList = explode('|', $getTicketsLink->getAttribute('data-times'));
                $link = $getTicketsLink->getAttribute('href');
                foreach ($timeList as $movieTime) {
                    $times[] = array(
                        'show_date' => $this->currentDate,
                        'show_time' => $movieTime,
                        'url' => $link ? "{$link}+{$movieTime}" : '',
                        'type' => $showtimeType,
                    );
                }
            } else {
                //for none links
                //$(movieDomList[4]).find('.showtimes').text().replace(/\s+/g, ' ').trim()
                //"1:00 pm | 3:30 | 6:15 | 9:00"
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
        if (preg_match('/$3D/i', $text) === 'showtimes') {
            return 'digital 3D';
        } elseif (preg_match('/imax/i', $text)) {
            return 'IMAX';
        } else {
            return 'digital';
        }
    }

}
