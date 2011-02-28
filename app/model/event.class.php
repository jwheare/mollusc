<?php

namespace App\Model;
use Core\Model;

class Event extends Model {
    var $table = 'event';
    protected $columns = array(
        'location',
        'action',
        'fare',
        'price_cap',
        'balance',
    );
    protected $getByColumns = array('creation_date', 'action');
    
    public function isExit () {
        return $this->action == 'Exit';
    }
}
