<?php

namespace Core\Controller;
use Core\Request;
use Core\Response;
use Core\HttpStatus;

abstract class Form extends Base {
    const ALLOWED_METHOD = 'POST';
    const ACCEPTED_TYPE = 'text/html';
    public function __construct (Request $request, $name, $action, array $args) {
        // Default response is a redirect to the current
        parent::__construct($request, $name, $action, $args, new Response\Redirect($request, new HttpStatus\SeeOther($request->getUrl())));
    }
}
