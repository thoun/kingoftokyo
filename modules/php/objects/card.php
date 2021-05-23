<?php
namespace KOT\Objects;

class Card {
    public $id;
    public $location;
    public $location_arg;
    public $type; // 0..100 for keep power, 100..200 for discard power
    public $tokens;
    public $mimicType;

    public function __construct($dbCard) {
        $this->id = intval($dbCard['id']);
        $this->location = $dbCard['location'];
        $this->location_arg = intval($dbCard['location_arg']);
        $this->type = intval($dbCard['type']);
        $this->tokens = intval($dbCard['type_arg']);
    } 
}
?>