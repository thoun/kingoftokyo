<?php
namespace KOT\Objects;


class Player {
    public int $id;
    public string $name;
    public string $color;
    public int $no;
    public int $score;
    public int $health;
    public int $turnLostHealth;
    public int $turnGainedHealth;
    public int $energy;
    public int $turnEnergy;
    public int $location;
    public bool $eliminated;
    public int $shrinkRayTokens;
    public int $poisonTokens;
    public int $wickedness;
    public int $zombified;
    public bool $turnEnteredTokyo;
    public int $askPlayEvolution;

    public function __construct($dbPlayer) {
        $this->id = intval($dbPlayer['player_id']);
        $this->name = $dbPlayer['player_name'];
        $this->color = $dbPlayer['player_color'];
        $this->no = intval($dbPlayer['player_no']);
        $this->score = intval($dbPlayer['player_score']);
        $this->health = intval($dbPlayer['player_health']);
        $this->turnLostHealth = intval($dbPlayer['player_turn_health']);
        $this->turnGainedHealth = intval($dbPlayer['player_turn_gained_health']);
        $this->energy = intval($dbPlayer['player_energy']);
        $this->turnEnergy = intval($dbPlayer['player_turn_energy']);
        $this->location = intval($dbPlayer['player_location']);
        $this->eliminated = boolval($dbPlayer['player_eliminated']) || (intval($dbPlayer['player_dead']) > 0);
        $this->shrinkRayTokens = intval($dbPlayer['player_shrink_ray_tokens']);
        $this->poisonTokens = intval($dbPlayer['player_poison_tokens']);
        $this->wickedness = intval($dbPlayer['player_wickedness']);
        $this->zombified = boolval($dbPlayer['player_zombified']);
        $this->turnEnteredTokyo = boolval($dbPlayer['player_turn_entered_tokyo']);
        $this->askPlayEvolution = array_key_exists('ask_play_evolution', $dbPlayer) ? intval($dbPlayer['ask_play_evolution']) : 0;
    } 
}
?>