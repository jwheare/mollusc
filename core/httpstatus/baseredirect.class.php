<?php

namespace Core\HttpStatus;
use Core\Url;

class BaseRedirect extends Base {
    protected $location;
    public function __construct($location = null) {
        $this->setLocation($location);
    }
    public function getLocation () {
        return Url::addHost($this->location);
    }
    public function setLocation ($location) {
        $this->location = $location;
    }
}
