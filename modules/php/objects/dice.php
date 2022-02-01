<?php
namespace KOT\Objects;

class Dice {
    public int $id;
    public int $value; // [1 2 3 heart energy smash] for die6, [eye river snake ankh] for die4
    public bool $extra;
    public bool $locked;
    public bool $rolled;
    public int $type;
    public bool $discarded;
    public bool $canReroll = true;

    public function __construct($dbDice) {
        $this->id = intval($dbDice['dice_id']);
        $this->value = intval($dbDice['dice_value']);
        $this->extra = boolval($dbDice['extra']);
        $this->locked = boolval($dbDice['locked']);
        $this->rolled = boolval($dbDice['rolled']);
        $this->type = array_key_exists('type', $dbDice) ? intval($dbDice['type']) : 0;
        $this->discarded = array_key_exists('discarded', $dbDice) ? boolval($dbDice['discarded']) : 0;
    } 
}
?>
