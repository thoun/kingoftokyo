<?php
namespace KOT\Objects;


class Player {
    public int $id;
    public string $name;
    public string $color;
    public int $no;
    public int $score;
    public int $health;
    public int $energy;
    public int $turnEnergy;
    public int $location;
    public bool $eliminated;
    public int $shrinkRayTokens;
    public int $poisonTokens;
    public int $wickedness;
    public int $zombified;

    public function __construct($dbPlayer) {
        $this->id = intval($dbPlayer['player_id']);
        $this->name = $dbPlayer['player_name'];
        $this->color = $dbPlayer['player_color'];
        $this->no = intval($dbPlayer['player_no']);
        $this->score = intval($dbPlayer['player_score']);
        $this->health = intval($dbPlayer['player_health']);
        $this->energy = intval($dbPlayer['player_energy']);
        $this->turnEnergy = intval($dbPlayer['player_turn_energy']);
        $this->location = intval($dbPlayer['player_location']);
        $this->eliminated = boolval($dbPlayer['player_eliminated']) || (intval($dbPlayer['player_dead']) > 0);
        $this->shrinkRayTokens = intval($dbPlayer['player_shrink_ray_tokens']);
        $this->poisonTokens = intval($dbPlayer['player_poison_tokens']);
        if (array_key_exists('player_wickedness', $dbPlayer)) {
            $this->wickedness = intval($dbPlayer['player_wickedness']);
        }
        if (array_key_exists('player_zombified', $dbPlayer)) {
            $this->zombified = boolval($dbPlayer['player_zombified']);
        }
    } 
}
?>