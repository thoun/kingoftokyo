<?php
namespace KOT\Objects;

class EvolutionCard {
    public int $id;
    public string $location;
    public int $location_arg;
    public int $type;
    public int $cardType; // 1 : permanent, 2 : temporary, 3 : gift

    public function __construct(array $dbCard) {
        $this->id = intval($dbCard['id']);
        $this->location = $dbCard['location'];
        $this->location_arg = intval($dbCard['location_arg']);
        $this->type = intval($dbCard['type']);
        $this->cardType = intval($dbCard['type_arg']);
    } 
}
?>