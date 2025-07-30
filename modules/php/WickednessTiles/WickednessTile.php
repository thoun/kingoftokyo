<?php
namespace Bga\Games\KingOfTokyo\WickednessTiles;

use \Bga\GameFrameworkPrototype\Item\Item;
use \Bga\GameFrameworkPrototype\Item\ItemField;

#[Item('wickedness_tile')]
class WickednessTile {
    #[ItemField(kind: 'id', dbField: 'card_id')]
    public int $id;
    #[ItemField(kind: 'location', dbField: 'card_location')]
    public string $location;
    #[ItemField(kind: 'location_arg', dbField: 'card_location_arg')]
    public ?int $location_arg;
    #[ItemField(dbField: 'card_type')]
    public int $type;
    #[ItemField(dbField: 'card_type_arg')]
    public int $tokens;
    #[ItemField(kind: 'order')]
    public ?int $order;
    
    public $mimicType;
    public $side; // 0 front, 1 back

    public function setup($dbCard) {
        $this->side = $this->type >= 100 ? 1 : 0;
    } 
}
?>
