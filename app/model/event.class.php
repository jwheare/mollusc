<?php

namespace App\Model;
use Core\Model;
use DateTime;

class Event extends Model {
    var $table = 'event';
    protected $columns = array(
        'end_date',
        'location',
        'action',
        'fare',
        'price_cap',
        'balance',
        'note',
    );
    protected function afterLoad() {
        if ($this->end_date && !$this->end_date instanceof DateTime) {
            $this->end_date = new DateTime($this->end_date);
        }
    }
    protected $getByColumns = array('creation_date', 'action');
    
    public function isExit () {
        return $this->action == 'Exit';
    }
    public function isJourney () {
        return $this->action == 'Journey';
    }
}
