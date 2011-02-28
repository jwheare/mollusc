<?php

namespace Core\Controller;
use Core\Request;
use Core\Response;

abstract class Json extends Base {
    const ACCEPTED_TYPE = 'application/json';
    public function __construct (Request $request, $name, $action, array $args) {
        parent::__construct($request, $name, $action, $args, new Response\Json($request));
    }
}
