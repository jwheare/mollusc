<?php

namespace Core\HttpStatus;

class Forbidden extends BaseError {
    protected $code = 403;
    protected $text = 'Forbidden';
}
