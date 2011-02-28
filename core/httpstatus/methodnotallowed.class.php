<?php

namespace Core\HttpStatus;
use Core\Response;

class MethodNotAllowed extends BaseError {
    protected $code = 405;
    protected $text = 'Method Not Allowed';
    protected $allowedMethods = array();
    public function setAllowedMethods ($allowedMethods) {
        $this->allowedMethods = $allowedMethods;
    }
    public function processResponse (Response\Base $response) {
        $response->setHeader('Allow', implode(', ', $this->allowedMethods));
        parent::processResponse($response);
    }
}
