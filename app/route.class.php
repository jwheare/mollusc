<?php

namespace App;

class Route {
    static function getPatterns () {
        return array(
            '/^$/' => 'home',
        );
    }
}
