<?php

namespace models\services;

use intelworx\tripican\apiclient\TripicanV1Client;

/**
 * Description of DataLoader
 *
 * @author intelWorX
 */
class TripicanDataLoader extends \IdeoObject
{

    /**
     * @param TripicanV1Client $apiClient
     * @param array $params
     * @param array $availableDates
     * @param null $currentShowdate
     * @return array
     */
    public static function GetCinemaMovieShowtimes(TripicanV1Client $apiClient, array $params, &$availableDates = [], &$currentShowdate = null)
    {
        $hasNextPage = false;
        $cinemaMovieShowtimes = [];
        $params['page'] = 0;
        //load movies from API
        do {
            ++$params['page'];
            $showtimesResult = $apiClient->moviesShowtimes($params);
            if ($showtimesResult !== null) {
                $hasNextPage = ($showtimesResult['pagination']['current_page'] < $showtimesResult['pagination']['total_pages']);
                $cinemaMovieShowtimes = array_merge($cinemaMovieShowtimes, $showtimesResult['cinema_movie_showtimes']);
                if (empty($availableDates)) {
                    $availableDates = $showtimesResult['show_dates'];
                }
                $currentShowdate = $showtimesResult['current_show_date'];
            }
        } while ($hasNextPage);

        return $cinemaMovieShowtimes;
    }

    /**
     * @param TripicanV1Client $apiClient
     * @return array
     */
    public static function LoadCinemas(TripicanV1Client $apiClient)
    {
        $page = 0;
        $cinemas = [];
        do {
            $params = [
                'page' => ++$page,
            ];
            $cinemasResult = $apiClient->moviesCinemas($params);
            if ($cinemasResult !== null) {
                $cinemas = array_merge($cinemas, $cinemasResult['cinemas']);
            }
        } while ($cinemasResult !== null && $cinemasResult['pagination']['current_page'] != $cinemasResult['pagination']['total_pages']);
        return $cinemas;
    }

    /**
     * @param TripicanV1Client $apiClient
     * @return array
     */
    public static function LoadCinemaGroups(TripicanV1Client $apiClient)
    {
        $cinemas = self::LoadCinemas($apiClient);
        $cinemaGroups = [];
        foreach ($cinemas as $cinema) {
            $cinemaGroups[$cinema['company']['id']] = $cinema['company'];
        }
        return $cinemaGroups;
    }

    /**
     * @param TripicanV1Client $apiClient
     * @return array
     */
    public static function LoadStatesWithCinemas(TripicanV1Client $apiClient)
    {
        $states = [];
        $params = [
            'with_cinemas' => 1,
        ];
        $result = $apiClient->listStates($params);
        if ($result !== null) {
            $states = $result['states'];
        }
        return $states;
    }

}
