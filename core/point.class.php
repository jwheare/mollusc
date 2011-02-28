<?php

namespace Core;

class Point {
    public $latitude;
    public $longitude;
    
    function __construct(array $coordinates) {
        $this->latitude = $coordinates[0];
        $this->longitude = $coordinates[1];
    }
}
