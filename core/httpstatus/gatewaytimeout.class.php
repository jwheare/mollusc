<?php

namespace Core\HttpStatus;

class GatewayTimeout extends BaseError {
    protected $code = 504;
    protected $text = 'Gateway Timeout';
}
