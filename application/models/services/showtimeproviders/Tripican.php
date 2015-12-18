<?php
/**
 * Created by PhpStorm.
 * User: intelWorx
 * Date: 18/12/2015
 * Time: 7:15 PM
 */

namespace models\services\showtimeproviders;


use intelworx\tripican\apiclient\TripicanV1Client;
use models\entities\GeocodeCached;
use models\entities\Showtime;
use models\services\ShowtimeServiceProvider;
use models\services\TripicanDataLoader;

/**
 * Class TripicanApi
 *
 * This class implements showtime service provider using Tripican.com's API
 *
 * The API is private, please get in touch with bizdev<at>tripican<dot>com if you need the Keys to the API
 *
 *
 * @package application\models\services\showtimeproviders
 *
 */
class Tripican extends ShowtimeServiceProvider
{

    private static $COUNTRIES = [
        'NG'
    ];

    /**
     * @var TripicanV1Client
     */
    private $apiClient;

    /**
     *
     * TripicanApi constructor.
     */
    public function __construct()
    {
        $this->supportedCountries = self::$COUNTRIES;
        $this->apiClient = new TripicanV1Client();
    }


    /**
     * @param GeocodeCached $geocode Previously stored geoLocation information
     * @param string $currentDate date for showtimes to load, advisable to pass it in
     * @param int $dateOffset number of days from the current dates
     * @return TheatreData[] an array of schema TheatreData, see below for description
     *
     * Organized in the format =>
     *
     * ```
     *
     *  TheatreData : [
     *      'theatre' => [
     *              ...fields from theatre table, MUST contain name and address...,
     *      ]
     *      'movies' => MovieData[]
     *  ]
     *
     * MovieData : [
     *     'movie' => [
     *          ...fields from movie table, MUST contain title...,
     *     ]
     *     'showtimes' => ShowtimeData[]
     * ]
     *
     * ShowtimeData: [
     *      ...fields from showtime table, must conatin show_date, show_time & type...
     * ]
     * ```
     */
    public function loadShowtimes(GeocodeCached $geocode, $currentDate = null, $dateOffset = 0)
    {
        $params = [
            'country' => $geocode->country_iso,
            'event_date' => \Utilities::dateFromOffset($currentDate ?: date('Y-m-d'), $dateOffset),
        ];

        \SystemLogger::debug('Begining API Request to Tripican');
        $start = microtime(true);
        $allCinemaMovieShowtimes = TripicanDataLoader::GetCinemaMovieShowtimes($this->apiClient, $params);
        \SystemLogger::debug('Request to API completed in :', microtime(true) - $start);

        $theatres = [];
        foreach ($allCinemaMovieShowtimes as $cinemaMovieShowtimes) {
            $theatre = $cinemaMovieShowtimes['cinema'];
            $id = $theatre['id'];
            if (!isset($theatres[$id])) {
                $theatres[$id] = [
                    'theatre' => ['name' => $theatre['centre_name'],
                        'address' => $this->_getAddress($theatre),
                        'longitude' => $theatre['place']['long'],
                        'latitude' => $theatre['place']['lat'],
                    ],
                    'movies' => []
                ];
            }
            $theatreData = &$theatres[$id];

            foreach ($cinemaMovieShowtimes['movie_showtimes'] as $movieShowtimes) {
                $movie = $movieShowtimes['movie'];
                $mId = $movie['id'];

                if (!isset($theatreData['movies'][$mId])) {
                    $theatreData['movies'][$mId] = [
                        'movie' => [
                            'title' => $movie['title'],
                            'genre' => $movie['genre'][0],
                            'user_rating' => isset($movie['imdb_rating']) ? (floatval($movie['imdb_rating'][0] / 10.0)) : null,
                            'poster_url' => $movie['poster'],
                            'rated' => $movie['rated'][0],
                            'runtime' => $movie['duration'],
                        ],
                        'showtimes' => []
                    ];
                }

                $movieData = &$theatreData['movies'][$mId];

                foreach ($movieShowtimes['showtimes'] as $showtime) {
                    $movieData['showtimes'][] = [
                        'show_date' => $showtime['event_date'],
                        'show_time' => $showtime['event_time'],
                        'type' => $this->_getMapType($showtime['type']),
                        'url' => $showtime['online_ticketing'] ? $showtime['url'] : null,
                    ];
                }
            }
        }

        array_walk($theatres, function (&$t) {
            $t['movies'] = array_values($t['movies']);
        });

        if (!empty($theatres)) {
            $this->overrideDistanceCompute();
        }
        return array_values($theatres);
    }

    /**
     * Since most cinemas returned already have GEOcode infor
     * Simply tell system to compute distance using Haversine Greate Circle
     */
    private function overrideDistanceCompute()
    {
        \SystemConfig::getInstance()
            ->overrideConfig('service', 'defer_distance_info', false)
            ->overrideConfig('service', 'physical_distance', false);
    }

    private function _getMapType($type)
    {
        if ($type === '3d') {
            return Showtime::TYPE_3D;
        } else if (strtolower($type) === 'imax') {
            return Showtime::TYPE_IMAX;
        } else {
            return Showtime::TYPE_2D;
        }
    }

    private function _getAddress($theatre)
    {
        $addressArr = [
            $theatre['address_line_1'],
            trim($theatre['address_line_2']),
            $theatre['address_city'],
            isset($theatre['state']) ? $theatre['state']['location_name'] : null
        ];
        return join(', ', array_filter($addressArr));
    }
}
