<?php
namespace KOT\Objects;

class Dice {
    public $id;
    public $value; // [1 2 3 heart energy smash] for die6, [eye river snake ankh] for die4
    public $extra;
    public $locked;
    public $rolled;
    public $type;
    public $canReroll = true;

    public function __construct($dbDice) {
        $this->id = intval($dbDice['dice_id']);
        $this->value = intval($dbDice['dice_value']);
        $this->extra = boolval($dbDice['extra']);
        $this->locked = boolval($dbDice['locked']);
        $this->rolled = boolval($dbDice['rolled']);
        $this->type = array_key_exists('type', $dbDice) ? intval($dbDice['type']) : 0;
    } 
}
?>
