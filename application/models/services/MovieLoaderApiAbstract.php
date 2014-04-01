<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\services;

/**
 * Description of MovieLoaderApiInterface
 *
 * @author intelWorX
 */
abstract class MovieLoaderApiAbstract extends \IdeoObject {

    protected $originalUrl;
    protected $title;
    protected $movie = null;

    const CONNECT_TIMEOUT = 15;

    protected $context;

    public function __construct($title) {
        //$this->originalUrl = $url;
        //$matches = array();
//        if (preg_match('/tt\d+/', $url, $matches)) {
//            $this->title = $matches[0];
//        }
        $this->title = $title;
        $this->context = $context = stream_context_create(array(
            'http' => array(
                'method' => 'GET',
                'user_agent' => "Mozilla/5.0 (Windows NT 6.2; WOW64)",
                'timeout' => self::CONNECT_TIMEOUT,
            ),
        ));
    }
   

    //put your code here
    public abstract function getMovieData();

    protected function loadMovieJSON($url) {
        \SystemLogger::debug("URL for Movie", $url);
        $json = file_get_contents($url, false, $this->context);
        \SystemLogger::debug("Response:", $json);
        return json_decode($json, true);
    }

}
