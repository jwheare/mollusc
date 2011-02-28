<?php

namespace Core\Response;
use Core\Request;
use Core\HttpStatus;
use Core\Url;

class Redirect extends Base {
    protected $redirect;
    public function __construct (Request $request, HttpStatus\BaseRedirect $redirect) {
        parent::__construct($request);
        $this->redirect = $redirect;
        $this->setStatus($this->redirect->getCode(), $this->redirect->getText());
    }
    public function beforeRespond () {
        $this->setHeader('Location', $this->redirect->getLocation());
    }
    public function setLocation ($location) {
        $this->redirect->setLocation($location);
    }
    public function addLocationParams ($params) {
        $this->redirect->setLocation(Url::mergeQueryParams($this->redirect->getLocation(), $params));
    }
}
