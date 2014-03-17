<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\services;

/**
 * Description of LocationServiceProvider
 *
 * @author intelWorX
 */
abstract class LocationServiceProvider implements \ComparableInterface {

    /**
     *
     * @var ServiceError
     * 
     */
    protected $lastError;
    protected $priority = 1000;

    /**
     * @return LookupResult Description
     */
    abstract public function lookUp($long, $lat);

    /**
     * 
     * @return ServiceError
     */
    public function getLastError($clear = true) {
        $lastErr = $this->lastError;
        if ($clear) {
            $this->lastError = null;
        }
        return $lastErr;
    }

    public function compare(\ComparableInterface $compareTo) {
        if ($this->priority > $compareTo->priority) {
            return 1;
        } else if ($this->priority < $compareTo->priority) {
            return -1;
        } else {
            return 0;
        }
    }

    protected function callUrl($url) {
        $contextOpt = array(
            'http' => array(
                'user_agent' => "pbMovies LocationServiceClient 1.0 +" . BASE_URL,
                'ignore_errors' => true,
                'timeout' => 30,
                'method' => 'GET',
            ),
        );

        $streamContext = stream_context_create($contextOpt);
        return file_get_contents($url, false, $streamContext);
    }

    protected function formatUrl($url, array $data, $encoded = false) {
        foreach ($data as $key => $value) {
            $url = str_replace("{{$key}}", $encoded ? $value : urlencode($value), $url);
        }

        return $url;
    }

}
