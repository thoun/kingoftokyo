<?php
namespace Bga\Games\KingOfTokyo\PowerCards;

use \Bga\GameFrameworkPrototype\Item\Item;
use \Bga\GameFrameworkPrototype\Item\ItemField;

#[Item('card')]
class PowerCard {
    #[ItemField(kind: 'id', dbField: 'card_id')]
    public int $id;
    #[ItemField(kind: 'location', dbField: 'card_location')]
    public string $location;
    #[ItemField(kind: 'location_arg', dbField: 'card_location_arg')]
    public ?int $location_arg;
    #[ItemField(dbField: 'card_type')]
    public int $type;
    #[ItemField(dbField: 'card_type_arg')]
    public int $type_arg;
    #[ItemField(kind: 'order')]
    public ?int $order;
    
    public ?int $mimicType = null;
    public int $tokens = 0;
    public int $side = 0; // 0 front, 1 back

    public function setup($dbCard) {
        $this->tokens = $this->type < 100 ? $this->type_arg : 0;
        $this->side = $this->type > 300 ? $this->type_arg : 0;
    } 
    
    public static function onlyId(?PowerCard $card): ?PowerCard {
        if (!$card) {
            return null;
        }
        $public = new PowerCard();
        $public->id = $card->id;
        $public->location = $card->location;
        $public->location_arg = $card->location_arg;
        $public->type = 0;
        $public->tokens = 0;
        $public->side = 0;

        return $public;
    }

    public static function onlyIds(array $cards): array {
        return array_map(fn($card) => self::onlyId($card), $cards);
    }
}
?>