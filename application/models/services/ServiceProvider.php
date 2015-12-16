<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\services;

define('DEFAULT_USER_AGENT', "pbMovies LocationServiceClient 1.0 +" . BASE_URL);

/**
 * Description of ServiceProvider
 *
 * @author intelWorX
 * 
 */
abstract class ServiceProvider extends \IdeoObject implements \ComparableInterface
{

    /**
     *
     * @var ServiceError
     *
     */
    protected $lastError;
    protected $priority = 1000;
    protected $userAgent = DEFAULT_USER_AGENT;

    /**
     *
     * @return ServiceError
     */
    public function getLastError($clear = true)
    {
        $lastErr = $this->lastError;
        if ($clear) {
            $this->lastError = null;
        }
        return $lastErr;
    }

    public function compare(\ComparableInterface $compareTo)
    {
        if ($this->priority > $compareTo->priority) {
            return 1;
        } else if ($this->priority < $compareTo->priority) {
            return -1;
        } else {
            return 0;
        }
    }

    protected function callUrl($url, $logResponse = true)
    {
        $contextOpt = array(
            'http' => array(
                'user_agent' => $this->userAgent,
                'ignore_errors' => true,
                'timeout' => 30,
                'method' => 'GET',
            ),
        );

        $streamContext = stream_context_create($contextOpt);
        \SystemLogger::info(get_class($this), ":", __METHOD__, "URL: ", $url);
        $result = file_get_contents($url, false, $streamContext);
        if ($logResponse) {
            \SystemLogger::info("Call returned: ", $result);
        }
        return $result;
    }

    protected function formatUrl($url, array $data, $encoded = false)
    {
        foreach ($data as $key => $value) {
            $url = str_replace("{{$key}}", $encoded ? $value : urlencode($value), $url);
        }

        return $url;
    }

}
