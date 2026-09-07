<?php
namespace Bga\Games\KingOfTokyo\CurseCards;

use Bga\GameFramework\Components\ItemManager\Item;
use Bga\GameFramework\Components\ItemManager\ItemField;
use Bga\GameFramework\Components\ItemManager\ItemFieldKind;

#[Item('curse_card')]
class CurseCard {
    #[ItemField(kind: ItemFieldKind::ID, dbField: 'card_id')]
    public int $id;
    #[ItemField(kind: ItemFieldKind::LOCATION, dbField: 'card_location')]
    public string $location;
    #[ItemField(kind: ItemFieldKind::LOCATION, dbField: 'card_location_arg', locationIndex: 1)]
    public ?int $location_arg;
    #[ItemField(dbField: 'card_type')]
    public int $type;
    #[ItemField(kind: ItemFieldKind::ORDER)]
    public int $order = 0;

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
