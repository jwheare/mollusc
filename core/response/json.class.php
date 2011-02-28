<?php

namespace Core\Response;
use Core\Request;

class Json extends Base {
    private $data;
    public function __construct (Request $request) {
        parent::__construct($request);
        $this->setHeader('Content-type', 'application/json; charset=utf-8');
    }
    public function __get ($name) {
        return $this->data[$name];
    }
    public function __set ($name, $value) {
       $this->data[$name] = $value;
    }
    public function setData ($data) {
        foreach ($data as $key => $value) {
            $this->data[$key] = $value;
        }
    }
    public function getBody () {
        return json_encode($this->data);
    }
}
