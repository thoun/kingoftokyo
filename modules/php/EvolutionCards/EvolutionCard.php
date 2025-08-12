<?php
namespace Bga\Games\KingOfTokyo\EvolutionCards;

use \Bga\GameFrameworkPrototype\Item\Item;
use \Bga\GameFrameworkPrototype\Item\ItemField;

#[Item('evolution_card')]
class EvolutionCard {
    #[ItemField(kind: 'id', dbField: 'card_id')]
    public int $id;
    #[ItemField(kind: 'location', dbField: 'card_location')]
    public string $location;
    #[ItemField(kind: 'location_arg', dbField: 'card_location_arg')]
    public ?int $location_arg;
    #[ItemField(dbField: 'card_type')]
    public int $type;
    #[ItemField(dbField: 'card_type_arg')]
    public int $tokens = 0;
    #[ItemField(kind: 'order')]
    public ?int $order;
    
    #[ItemField(dbField: 'owner_id')]
    public ?int $ownerId;
    
    public static function createBackCard(int $id) {
        $public = new EvolutionCard();
        $public->id = $id;
        $public->location = '';
        $public->location_arg = 0;
        $public->type = 0;
        $public->tokens = 0;
        $public->ownerId = 0;

        return $public;
    }
}
?>