<?php

namespace Core\HttpStatus;

class BadGateway extends BaseError {
    protected $code = 502;
    protected $text = 'Bad Gateway';
}
