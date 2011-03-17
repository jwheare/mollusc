<?php

namespace App\Controller;
use Core\Controller;
use App\Model;

class HomeHtml extends Controller\Html {
    public function index () {
        $this->session->nocache();
        
        $e = new Model\Event;
        list($this->events, $this->totalEvents) = $e->getAll();
        return $this->response;
    }
}
