<?php

namespace Core\HttpStatus;

class Unauthorized extends BaseError {
    protected $code = 401;
    protected $text = 'Unauthorized';
}
