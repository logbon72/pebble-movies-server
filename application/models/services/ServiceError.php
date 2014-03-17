<?php

namespace models\services;

/**
 * Description of ServiceError
 * 
 * 
 * @author intelWorX
 */
class ServiceError extends \IdeoObject {
    
    protected $errorType = null;
    protected $errorMessage = null;
    
    const ERR_RATE_LIMIT = 'rateLimit';
    const ERR_NOT_FOUND = 'notFound';
    
    public function __construct($type, $message = "") {
        $this->errorMessage = $message;
        $this->errorType = $type;
    }
    
    public function getMessage() {
        return $this->errorMessage;
    }
    
    public function getType() {
        return $this->errorType;
    }

    public function isNotFound(){
        return $this->errorType === self::ERR_NOT_FOUND;
    }
    
    public function isRateLimit(){
        return $this->errorType === self::ERR_RATE_LIMIT;
    }
}
