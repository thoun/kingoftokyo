<?php
namespace Bga\Games\KingOfTokyo\CurseCards;

use \Bga\GameFrameworkPrototype\Item\Item;
use \Bga\GameFrameworkPrototype\Item\ItemField;

#[Item('curse_card')]
class CurseCard {
    #[ItemField(kind: 'id', dbField: 'card_id')]
    public int $id;
    #[ItemField(kind: 'location', dbField: 'card_location')]
    public string $location;
    #[ItemField(kind: 'location_arg', dbField: 'card_location_arg')]
    public ?int $location_arg;
    #[ItemField(dbField: 'card_type')]
    public int $type;
    #[ItemField(kind: 'order')]
    public ?int $order;

    public static function onlyId(?CurseCard $card) {
        if ($card == null) {
            return null;
        }

        $public = new CurseCard();
        $public->id = $card->id;
        $public->location = $card->location;
        $public->location_arg = $card->location_arg;
        $public->type = 0;        
        return $public;
    }
}
?>
