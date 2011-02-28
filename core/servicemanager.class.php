<?php

namespace Core;

class ServiceManager {
    static $services = array();
    
    public function register($name, $service) {
        self::$services[strtolower($name)] = $service;
    }
    public function get($name) {
        return self::$services[strtolower($name)];
    }
}
