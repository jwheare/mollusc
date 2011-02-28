<?php

namespace Core\HttpStatus;

class NotImplemented extends BaseError {
    protected $code = 501;
    protected $text = 'Not Implemented';
}
