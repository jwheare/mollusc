<?php

namespace Core\Controller;
use Core\Request;

abstract class Shared {
    private $controller;
    private $name;
    private $action;
    public function __construct (Base $controller, $name, $action) {
        $this->controller = $controller;
        $this->name = $name;
        $this->action = $action;
    }
    public function __get ($name) {
        return $this->controller->$name;
    }
    public function __set ($name, $value) {
        $this->controller->$name = $value;
    }
    public function __call ($method, $args) {
        return call_user_func_array(array($this->controller, $method), $args);
    }
    protected function action () {
        if (!method_exists($this, $this->action)) {
            return false;
        }
        return $this->{$this->action}();
    }
    public function setUp () {
        // Override in subclass
    }
    public function processSetUp () {
        $this->setUp();
        $this->action();
    }
}