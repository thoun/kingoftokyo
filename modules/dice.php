<?php
class Dice {
    public $id;
    public $value;
    public $extra;

    public function __construct($dbDice) {
        $this->id = intval($dbDice['dice_id']);
        $this->value = intval($dbDice['dice_value']);
        $this->extra = boolval($dbDice['extra']);
    } 
}
?>
