<?php

namespace Core\HttpStatus;
use Core\Response;
use Exception;

abstract class BaseError extends Base {
    public function __construct($message = null, Exception $previous = null) {
        parent::__construct($message, $this->code, $previous);
    }
    public function processResponse (Response\Base $response) {
        
    }
}
