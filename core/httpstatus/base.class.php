<?php

namespace Core\HttpStatus;
use Exception;

function getErrorMap () {
    return array(
        400 => 'BadRequest',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'NotFound',
        405 => 'MethodNotAllowed',
        406 => 'NotAcceptable',
        500 => 'InternalServerError',
        501 => 'NotImplemented',
        502 => 'BadGateway',
        503 => 'ServiceUnavailable',
        504 => 'GatewayTimeout',
    );
}

class Base extends Exception {
    protected $text;
    protected $template;
    public function getTemplate () {
        return $this->template;
    }
    public function setTemplate ($template) {
        $this->template = $template;
    }
    public function getText() {
        return $this->text;
    }
    public function getStatus () {
        return "{$this->getCode()} {$this->getText()}";
    }
    public static function mapCodeToStatus ($code) {
        $errorMap = getErrorMap();
        if (array_key_exists($code, $errorMap)) {
            $httpErrorClass = __NAMESPACE__ . "\\" . $errorMap[$code];
            return $httpErrorClass;
        }
        return null;
    }
    public static function mapAuthException (Exception $exception) {
        $httpErrorClass = self::mapCodeToStatus($exception->getCode());
        return new $httpErrorClass($exception->getMessage(), $exception);
    }
}
