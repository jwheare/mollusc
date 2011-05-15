<?php

namespace App;

class Route {
    static function getPatterns (\Core\Request $request) {
        return array(
            // '/.*/' => 'offline', // uncomment to put up an offline page
            '/^$/' => 'home',
        );
    }
}
