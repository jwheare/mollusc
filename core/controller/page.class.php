<?php

namespace Core\Controller;
use Core\Request;
use Core\Response;

class Page extends Html {
    public function __construct (Request $request, $page, $action, $args) {
        Base::__construct($request, $page, $action, $args, new Response\Html($request));
        $this->response->setTemplate("_page/{$page}");
    }
    public function exists () {
        return $this->response->templateExists();
    }
    public function index () {
        return $this->response;
    }
}
