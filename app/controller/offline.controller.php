<?php

namespace App\Controller;
use Core\Controller;
use Core\HttpStatus;

class OfflineShared extends Controller\Shared {
    public function setUp () {
        $offline = new HttpStatus\ServiceUnavailable();
        $offline->setTemplate('offline');
        throw $offline;
    }
}
