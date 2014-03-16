<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace main\models;

/**
 * Description of Response
 *
 * @author intelWorX
 */
class Response extends \IdeoObject {

    protected $errors = array();
    protected $result = array();
    protected $pretty = false;

    const XML_ROOT = "apiResponse";

    protected $entityName = "result";

    const FORMAT_JSON = "json";
    const FORMAT_XML = "xml";
    const DEFAULT_FORMAT = self::FORMAT_JSON;

    protected $format = self::FORMAT_JSON;

    public function setFormat($format = self::FORMAT_JSON) {
        $this->format = $format;
    }

    //header('Content-Type: text/plain; charset=utf8');
    public function unAuthorized() {
        header('HTTP/1.1 401 Unauthorized', true);
        header('WWW-Authenticate: OAuth realm=""');
    }
    
    public function forbidden() {
        header('HTTP/1.1 403 Access denied', true);
    }

    public function notFound() {
        header('HTTP/1.1 404 Not Found', true);
    }

    public function badRequest() {
        header('HTTP/1.1 400 Bad Request', true);
    }

    public function serverError() {
        header('HTTP/1.1 503 Internal Server Error', true);
    }
    
    public function success() {
        header('HTTP/1.1 200 OK', true);
    }

    public function setAsJSON() {
        header('Content-Type: application/json; charset=utf8', true);
    }

    public function setAsHTML() {
        header('Content-Type: text/html; charset=utf8', true);
    }

    public function setAsXML() {
        header('Content-Type: text/xml; charset=utf8', true);
    }

    public function setAsPlain() {
        header('Content-Type: text/plain; charset=utf8', true);
    }

    public function output($return = false, $setStatus = true) {
        $output = array();
        if ($this->format == self::FORMAT_XML) {
            $this->setAsXML();
        } else {
            $this->format = self::DEFAULT_FORMAT;
            $this->setAsJSON();
        }

        if ($this->hasErrors()) {
            if ($setStatus) {
                $this->badRequest();
            }
            $output['success'] = 0;
            $output['errors'] = $this->errors;
        } else {
            if ($setStatus) {
                $this->success();
            }
            $output['success'] = 1;
            $output = array_merge($output, $this->result);
        }

        $formatted = ResponseFormatterFactory::getFormatter($this->format)
                ->format($output, \ApplicationFrontEnd::getRequest()->getRequest("pretty"));
        if ($return) {
            return $formatted;
        } else {
            echo $formatted;
        }
    }

    public function hasErrors() {
        return !empty($this->errors);
    }

    public function clearErrors() {
        $this->errors = array();
    }

    public function addError(ApiError $error) {
        $this->errors[] = $error->asArray();
        return $this;
    }

    public function getErrors() {
        return $this->errors;
    }

    public function setResult($result) {
        $this->result = $result;
    }

    public function getResult() {
        return $this->result;
    }

    public function setEntityName($name) {
        $this->entityName = $name;
        return $this;
    }

    public function getEntityName() {
        return $this->entityName;
    }
    
    public function getFormat() {
        return $this->format;
    }

}
