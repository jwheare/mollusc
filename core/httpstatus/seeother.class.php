<?php

namespace Core\HttpStatus;

class SeeOther extends BaseRedirect {
    protected $code = 303;
    protected $text = 'See Other';
}
