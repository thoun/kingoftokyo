<?php
namespace Bga\Games\KingOfTokyo\WickednessTiles;

use Bga\GameFramework\Components\ItemManager\Item;
use Bga\GameFramework\Components\ItemManager\ItemField;
use Bga\GameFramework\Components\ItemManager\ItemFieldKind;

#[Item('wickedness_tile')]
class WickednessTile {
    #[ItemField(kind: ItemFieldKind::ID, dbField: 'card_id')]
    public int $id;
    #[ItemField(kind: ItemFieldKind::LOCATION, dbField: 'card_location')]
    public string $location;
    #[ItemField(kind: ItemFieldKind::LOCATION, dbField: 'card_location_arg', locationIndex: 1)]
    public ?int $location_arg;
    #[ItemField(dbField: 'card_type')]
    public int $type;
    #[ItemField(dbField: 'card_type_arg')]
    public int $tokens;
    #[ItemField(kind: ItemFieldKind::ORDER)]
    public int $order = 0;
    
    public $mimicType;
    public $side; // 0 front, 1 back

    public function setup($dbCard) {
        $this->side = $this->type >= 100 ? 1 : 0;
    } 
}
?>
