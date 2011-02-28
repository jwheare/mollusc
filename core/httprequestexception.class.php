<?php

namespace Core;
use \Exception;
use HttpStatus;

class HttpRequestException extends Exception {
    protected $httpError;
    protected $method;
    protected $url;
    protected $response;
    protected $responseHeaders;
    
    public function __construct ($httpError, $method, $url, $params, $response = '', $responseHeaders = array(), $message = null) {
        $this->method = $method;
        $this->url = $url;
        $this->params = $params;
        $this->response = $response;
        $this->responseHeaders = $responseHeaders;
        $this->httpError = new $httpError($message, $this);
        parent::__construct($message, $this->getHttpCode());
    }
    
    public function __toString () {
        return get_called_class() . " {$this->getHttpStatus()}: {$this->getMethod()} {$this->getUrl()}";
    }
    public function getUrl() {
        return $this->url;
    }
    public function getMethod() {
        return $this->method;
    }
    public function getHttpError () {
        return $this->httpError;
    }
    public function getHttpCode () {
        return $this->getHttpError()->getCode();
    }
    public function getHttpStatus () {
        return $this->getHttpError()->getStatus();
    }
    public function getResponse () {
        return $this->response;
    }
    public function getResponseHeaders () {
        return $this->responseHeaders;
    }
}
