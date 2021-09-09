<?php
namespace KOT\Objects;


class Player {
    public $id;
    public $name;
    public $color;
    public $no;
    public $score;
    public $health;
    public $energy;
    public $location;
    public $eliminated;
    public $shrinkRayTokens;
    public $poisonTokens;

    public function __construct($dbPlayer) {
        $this->id = intval($dbPlayer['player_id']);
        $this->name = $dbPlayer['player_name'];
        $this->color = $dbPlayer['player_color'];
        $this->no = intval($dbPlayer['player_no']);
        $this->score = intval($dbPlayer['player_score']);
        $this->health = intval($dbPlayer['player_health']);
        $this->energy = intval($dbPlayer['player_energy']);
        $this->location = intval($dbPlayer['player_location']);
        $this->eliminated = boolval($dbPlayer['player_eliminated']) || boolval($dbPlayer['player_dead']);
        $this->shrinkRayTokens = intval($dbPlayer['player_shrink_ray_tokens']);
        $this->poisonTokens = intval($dbPlayer['player_poison_tokens']);
    } 
}
?>