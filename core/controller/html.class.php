<?php

namespace Core\Controller;
use Core\Request;
use Core\Response;

abstract class Html extends Base {
    const ALLOWED_METHOD = 'GET';
    const ACCEPTED_TYPE = 'text/html';
    public function __construct (Request $request, $name, $action, array $args) {
        parent::__construct($request, $name, $action, $args, new Response\Html($request));
        // Set the default template
        $this->response->setTemplate("{$this->getName()}/{$this->getAction()}");
    }
}
