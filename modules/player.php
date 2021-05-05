<?php
namespace KOT;


class Player {
    public $id;
    public $name;
    public $no;
    public $health;
    public $location;
    public $eliminated;

    public function __construct($dbPlayer) {
        $this->id = intval($dbPlayer['player_id']);
        $this->name = $dbPlayer['player_name'];
        $this->no = intval($dbPlayer['player_no']);
        $this->health = intval($dbPlayer['player_health']);
        $this->location = intval($dbPlayer['player_location']);
        $this->eliminated = boolval($dbPlayer['player_eliminated']);
    } 
}
?>
