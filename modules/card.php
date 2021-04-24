<?php
namespace KOT;

class Card {
    public $id;
    public $location;
    public $location_arg;
    public $type; // 0 for discard power, 1 for keep power
    public $number;

    public function __construct($dbCard) {
        $this->id = intval($dbCard['id']);
        $this->location = $dbCard['location'];
        $this->location_arg = intval($dbCard['location_arg']);
        $this->type = intval($dbCard['type']);
        $this->number = intval($dbCard['type_arg']);
    } 
}
?>