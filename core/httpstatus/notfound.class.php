<?php

namespace Core\HttpStatus;

class NotFound extends BaseError {
    protected $code = 404;
    protected $text = 'Not Found';
}
