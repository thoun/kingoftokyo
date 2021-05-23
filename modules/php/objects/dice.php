<?php
namespace KOT\Objects;

class Dice {
    public $id;
    public $value;
    public $extra;
    public $locked;
    public $rolled;

    public function __construct($dbDice) {
        $this->id = intval($dbDice['dice_id']);
        $this->value = intval($dbDice['dice_value']);
        $this->extra = boolval($dbDice['extra']);
        $this->locked = boolval($dbDice['locked']);
        $this->rolled = boolval($dbDice['rolled']);
    } 
}
?>