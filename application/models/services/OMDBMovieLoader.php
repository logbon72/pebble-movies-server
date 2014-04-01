<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\services;

class OMDBMovieLoader extends MovieLoaderApiAbstract {

    public function getMovieData() {
        if (!$this->title) {
            return null;
        }
        
        $data = $this->loadMovieJSON(sprintf("http://www.omdbapi.com/?t=%s", rawurlencode($this->title)));
        //debug_op($data, true);
        if (!empty($data)) {
            //$title = $data['Title'];
            $movieData = array(
                'title' => $data['Title'],
                'runtime' => $this->strToMinutes($data['Runtime']),
                'genre' => $data['Genre'],
                'rated' => $data['Rated'],
                'user_rating' => floatval($data['imdbRating']) / 10,
                'critic_rating' => floatval($data['Metascore']) / 100,
                'poster_url' => $data['Poster'],
                    //'trailer_url' => array($this->searchTrailerOnYouTube($title)),
            );

            return $movieData;
        }

        return array();
    }

    protected function strToMinutes($time) {
        //1 h 35 min
        $matches = array();
        if (preg_match_all("/((\d+)\s*h)?\s*((\d+)\s*min)?/", $time, $matches)) {
            return intval($matches[2][0]) * 60 + intval($matches[4][0]);
        }
        return 0;
    }

}
