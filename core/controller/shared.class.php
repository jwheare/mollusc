<?php

namespace Core\Controller;
use Core\Request;

abstract class Shared extends Base {
    protected $controller;
    public function __construct (Request $request, $name, $action, array $args, Base $controller = null) {
        parent::__construct($request, $name, $action, $args);
        
        $this->controller = $controller;
    }
    public function __get ($name) {
        return $this->controller->$name;
    }
    public function __set ($name, $value) {
        $this->controller->$name = $value;
    }
    public function hasAction () {
        return method_exists($this, $this->action);
    }
    public function action () {
        if (!$this->hasAction()) {
            return null;
        }
        return $this->{$this->action}();
    }
    public function setUp () {
        // Override in subclass
    }
}