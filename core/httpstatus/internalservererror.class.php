<?php

namespace Core\HttpStatus;

class InternalServerError extends BaseError {
    protected $code = 500;
    protected $text = 'Internal Server Error';
}
