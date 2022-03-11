<?php
namespace KOT\Objects;

class EvolutionCard {
    public int $id;
    public string $location;
    public int $location_arg;
    public int $type;
    public int $tokens;

    public function __construct(array $dbCard) {
        $this->id = intval($dbCard['id']);
        $this->location = $dbCard['location'];
        $this->location_arg = intval($dbCard['location_arg']);
        $this->type = intval($dbCard['type']);
        $this->tokens = intval($dbCard['type_arg']);
    }

    public static function createBackCard(int $id) {
        return new EvolutionCard([
            'id' => $id,
            'location' => '',
            'location_arg' => 0,
            'type' => 0,
            'type_arg' => 0,
        ]);
    }
}
?>