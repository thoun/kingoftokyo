<?php
namespace KOT\Objects;

class Card {
    public int $id;
    public string $location;
    public int $location_arg;
    public int $type; // 0..100 for keep power, 100..200 for discard power, 200..300 costume, 300..400 trnasformation
    public int $side; // 0 front, 1 back
    public int $tokens;
    public int $mimicType;

    public function __construct($dbCard) {
        $this->id = intval($dbCard['id']);
        $this->location = $dbCard['location'];
        $this->location_arg = intval($dbCard['location_arg']);
        $this->type = intval($dbCard['type']);
        $this->tokens = $this->type < 100 ? intval($dbCard['type_arg']) : 0;
        $this->side = $this->type > 300 ? intval($dbCard['type_arg']) : 0;
    } 
}
?>