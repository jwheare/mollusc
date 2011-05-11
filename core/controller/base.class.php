<?php

namespace Core\Controller;
use Core\Request;
use Core\Response;

abstract class Base {
    public $request;
    public $response;
    private $name;
    private $action;
    private $args;
    public function __construct (Request $request, $name, $action, array $args, Response\Base $response) {
        $this->request = $request;
        $this->name = $name;
        $this->action = $action;
        $this->args = $args;
        $this->response = $response;
    }
    public function getName() {
        return $this->name;
    }
    public function getAction() {
        return $this->action;
    }
    public function getArgs() {
        return $this->args;
    }
    public function __get ($name) {
        return $this->response->$name;
    }
    public function __set ($name, $value) {
        $this->response->$name = $value;
    }
    public function arg ($key, $default = null) {
        return array_key_exists($key, $this->args) ? $this->args[$key] : $default;
    }
    public function generateResponse () {
        return $this->{$this->action}();
    }
}
