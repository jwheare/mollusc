<?php

namespace Core\Controller;
use Core\Request;
use Core\Response;

abstract class Base {
    public $request;
    public $response;
    protected $session;
    protected $user;
    protected $name;
    protected $action;
    protected $args;
    public function __construct (Request $request, $name, $action, array $args, Response\Base $response = null) {
        $this->request = $request;
        
        $this->session = $this->request->getSession();
        $this->user = $this->session->getUser();
        
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
    protected function action () {
        return $this->{$this->action}();
    }
    public function generateResponse () {
        return $this->action();
    }
}
