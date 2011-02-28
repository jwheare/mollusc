<?php

namespace Core\HttpStatus;

class Found extends BaseRedirect {
    protected $code = 302;
    protected $text = 'Found';
}
