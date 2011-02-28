<?php

namespace Core\HttpStatus;

class BadRequest extends BaseError {
    protected $code = 400;
    protected $text = 'Bad Request';
}
