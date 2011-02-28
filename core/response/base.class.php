<?php

namespace Core\Response;
use Core\Request;
use Core\HttpStatus;
use Core\Dump;

abstract class Base {
    private $headers = array();
    private $statusCode = 200;
    private $statusText = 'OK';
    public $request;
    public $session;
    public $user;
    protected $exception;
    public function __construct (Request $request) {
        $this->request = $request;
        $this->session = $this->request->getSession();
        $this->user = $this->session->getUser();
    }
    public function getStatus () {
        return "{$this->statusCode} {$this->statusText}";
    }
    public function setStatus($statusCode, $statusText) {
        $this->statusCode = $statusCode;
        $this->statusText = $statusText;
    }
    public function setException (HttpStatus\BaseError $exception) {
        $this->exception = $exception;
        $this->setStatus($this->exception->getCode(), $this->exception->getText());
    }
    public function setHeader ($name, $value) {
        $this->headers[$name] = $value;
    }
    public function setHeaders ($headers) {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
    }
    public static function setCookie ($key, $value, $expires) {
        setcookie($key, $value, time() + $expires, '/', "." . HOST_NAME);
    }
    public static function deleteCookie ($key) {
        self::setCookie($key, '', -60*60*24);
    }
    public function getBody () {
        return '';
    }
    public function beforeRespond () {
        
    }
    public function respond () {
        $this->beforeRespond();
        if (!headers_sent()) {
            header("HTTP/1.1 {$this->getStatus()}");
            foreach ($this->headers as $name => $value) {
                header("$name: $value");
            }
        }
        $body = $this->getBody();
        if (!headers_sent()) {
            header("Content-Length: " . strlen($body));
        }
        echo $body;
    }
}
