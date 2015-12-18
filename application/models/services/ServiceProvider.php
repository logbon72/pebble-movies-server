<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\services;

define('DEFAULT_USER_AGENT', "pbMovies LocationServiceClient 1.0 +" . BASE_URL);

define('REQUESTS_LOG_DIR', APP_ROOT . 'http-debug/');

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

    private function isGzipEncoded(array $headers){
        foreach($headers as $header){
            $matches = [];
            if(preg_match('/^Content-Encoding:\s*.*(gzip|deflate)/', $header, $matches)){
                return true;
            }
        }
        return false;
    }

    private function getResponseContent($data, $headers){
      if($this->isGzipEncoded($headers)){
          \SystemLogger::debug('Data response is Gzip encoded');
          \SystemLogger::debug('Raw length is: ', strlen($data));
          return gzdecode($data);
      } else {
          return $data;
      }
    }

    protected function callUrl($url, $logResponse = null)
    {
        $startTime = microtime(true);
        $contextOpt = array(
            'http' => array(
                'user_agent' => $this->userAgent,
                'ignore_errors' => true,
                'timeout' => 30,
                'method' => 'GET',
                'header'=> [
                    'Accept-Encoding: gzip, deflate'
                ],
            ),
        );

        $streamContext = stream_context_create($contextOpt);
        \SystemLogger::debug(get_class($this), ":", __METHOD__, "URL: ", $url);
        $raw = file_get_contents($url, false, $streamContext);
        $result = $this->getResponseContent($raw, $http_response_header);
        \SystemLogger::debug("Response length: ", strlen($result));


        if ($logResponse === null) {
            $logResponse = $this->isLoggingRequests();
        }

        if ($logResponse) {
            $this->logRequest($url, $result, $http_response_header);
        }

        \SystemLogger::debug(get_class($this), ":", 'Completed request in : ', (microtime(true) - $startTime), 'ms');
        return $result;
    }

    public function logRequest($url, $response, array $headers, &$file = null)
    {
        $contentType = 'text/html';
        foreach ($headers as $header) {
            if (preg_match('/^Content-Type/', $header)) {
                //Content-Type: application/json; charset=utf-8
                $tmp1 = preg_split('/\s*:\s*/', $header);
                $contentType = trim(explode(';', $tmp1[1])[0]);
                break;
            }
        }

        if (preg_match('/html/i', $contentType)) {
            $ext = 'html';
        } elseif (preg_match('/json/i', $contentType)) {
            $ext = 'json';
        } elseif (preg_match('/xml/i', $contentType)) {
            $ext = 'xml';
        } else {
            $ext = 'txt';
        }

        $uriParts = parse_url($url);
        $fileDir = REQUESTS_LOG_DIR . $uriParts['host'] . DIRECTORY_SEPARATOR;
        if (!is_dir($fileDir) && !mkdir($fileDir, 0755, true)) {
            \SystemLogger::warn('Could not make directory:', $fileDir);
            return -1;
        }

        $file = $fileDir . join('.', [preg_replace('/[^A-Za-z0-9\._\-]+/', '', $uriParts['path']), microtime(true),
                $ext]);

        return file_put_contents($file, $response);
    }

    protected function formatUrl($url, array $data, $encoded = false)
    {
        foreach ($data as $key => $value) {
            $url = str_replace("{{$key}}", $encoded ? $value : urlencode($value), $url);
        }

        return $url;
    }

    protected function isLoggingRequests()
    {
        return !!\SystemConfig::getInstance()->service['log_requests'];
    }
}
