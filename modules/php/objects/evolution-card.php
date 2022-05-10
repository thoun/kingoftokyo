<?php
namespace KOT\Objects;

class EvolutionCard {
    public int $id;
    public string $location;
    public int $location_arg;
    public int $type;
    public int $tokens;
    public int $ownerId;

    public function __construct(array $dbCard) {
        $this->id = intval($dbCard['card_id']);
        $this->location = $dbCard['card_location'];
        $this->location_arg = intval($dbCard['card_location_arg']);
        $this->type = intval($dbCard['card_type']);
        $this->tokens = intval($dbCard['card_type_arg']);
        $this->ownerId = intval($dbCard['owner_id']);
    }

    public static function createBackCard(int $id) {
        return new EvolutionCard([
            'card_id' => $id,
            'card_location' => '',
            'card_location_arg' => 0,
            'card_type' => 0,
            'card_type_arg' => 0,
            'owner_id' => 0,
        ]);
    }
}
?>