<?php
namespace KOT\Objects;

class Card {
    public int $id;
    public string $location;
    public int $location_arg;
    public int $type; // 0..100 for keep power, 100..200 for discard power, 200..300 costume, 300..400 trnasformation
    public int $side; // 0 front, 1 back
    public int $tokens;
    public /*int|null*/ $mimicType;

    public function __construct($dbCard) {
        $this->id = intval($dbCard['id']);
        $this->location = $dbCard['location'];
        $this->location_arg = intval($dbCard['location_arg']);
        $this->type = intval($dbCard['type']);
        $this->tokens = $this->type < 100 ? intval($dbCard['type_arg']) : 0;
        $this->side = $this->type > 300 ? intval($dbCard['type_arg']) : 0;
    } 

    public static function onlyId(?Card $card) {
        if ($card == null) {
            return null;
        }
        
        return new Card([
            'id' => $card->id,
            'location' => $card->location,
            'location_arg' => $card->location_arg,
            'type' => $card->type < 200 ? 0 : -floor($card->type / 100) * 100,
            'type_arg' => 0,
        ]);
    }

    public static function onlyIds(array $cards) {
        return array_map(fn($card) => self::onlyId($card), $cards);
    }
}
?>