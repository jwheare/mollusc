<?php

namespace Core\HttpStatus;

class ServiceUnavailable extends BaseError {
    protected $code = 503;
    protected $text = 'Service Unavailable';
}
