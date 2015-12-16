<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\services;
use models\entities\GeocodeCached;

/**
 * Description of ShowtimeServiceProvider
 *
 * @author intelWorX
 */
abstract class ShowtimeServiceProvider extends ServiceProvider
{

    /**
     *
     * @var array
     */
    protected $supportedCountries = array();

    protected $userAgent = "Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.154 Safari/537.36";

    /**
     * @param GeocodeCached $geocode Previously stored geoLocation information
     * @param string $date date for showtimes to load, advisable to pass it in
     * @return array Theatre Movie Showtime: Organized in the format =>
     *  array(
     *      ('theatre' => array(
     *              [fieldsOfTheatreEntity]
     *              'movies' =>  array(
     *                  [fieldsOfMovieentity],
     *                  'showtimes' => [fieldsOfShowtimeEntity][]
     *              )[]
     *      )[]),
     *  );
     *
     *
     */
    abstract public function loadShowtimes(GeocodeCached $geocode, $date = null, $offset = 0);

    public function supports(GeocodeCached $locationInfo)
    {
        if (empty($this->supportedCountries)) {
            return true;
        }

        return in_array($locationInfo->country_iso, $this->supportedCountries);
    }

    public function getSupportedCountries()
    {
        return $this->supportedCountries;
    }

    protected function isLoggingRequests()
    {
        return !!\SystemConfig::getInstance()->service['log_requests'];
    }

}
