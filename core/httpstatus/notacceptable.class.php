<?php

namespace Core\HttpStatus;

class NotAcceptable extends BaseError {
    protected $code = 406;
    protected $text = 'Not Acceptable';
    protected $acceptedMimeTypes = array();
    public function setAcceptedMimeTypes ($acceptedMimeTypes) {
        $this->acceptedMimeTypes = $acceptedMimeTypes;
    }
}
