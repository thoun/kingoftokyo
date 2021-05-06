<?php
namespace KOT;

class Card {
    public $id;
    public $location;
    public $location_arg;
    public $type; // 0..100 for keep power, 100..200 for discard power
    public $cost;

    public function __construct($dbCard, $CARD_COST) {
        $this->id = intval($dbCard['id']);
        $this->location = $dbCard['location'];
        $this->location_arg = intval($dbCard['location_arg']);
        $this->type = intval($dbCard['type']);
        $this->cost = $CARD_COST[$this->type];
    } 
}
?>