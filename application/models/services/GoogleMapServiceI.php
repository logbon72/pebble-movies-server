<?php
/**
 * Created by PhpStorm.
 * User: intelWorx
 * Date: 09/01/2016
 * Time: 3:02 PM
 */

namespace models\services;


interface GoogleMapServiceI
{

    const STATUS_OK = "OK";
    const STATUS_ZERO_RESULTS = "ZERO_RESULTS";
    const STATUS_OVER_QUERY_LIMIT = "OVER_QUERY_LIMIT";
    const STATUS_REQUEST_DENIED = "REQUEST_DENIED";
    const STATUS_INVALID_REQUEST = "INVALID_REQUEST";
    const STATUS_UNKNOWN_ERROR = "UNKNOWN_ERROR";


    function hasError($result);
}