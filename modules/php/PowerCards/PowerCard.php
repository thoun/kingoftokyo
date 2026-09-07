<?php
namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\GameFramework\Components\ItemManager\Item;
use Bga\GameFramework\Components\ItemManager\ItemField;
use Bga\GameFramework\Components\ItemManager\ItemFieldKind;
use Bga\Games\KingOfTokyo\Objects\ActivatedConsumableKeyword;

const HUNTER = 'HUNTER';
const SNEAKY = 'SNEAKY';
const POISON = 'POISON';
const TOUGH = 'TOUGH';
const FRENZY = 'FRENZY';
const MINDBUG_KEYWORDS_START_TURN = [HUNTER, SNEAKY];
const MINDBUG_KEYWORDS_WOUNDED = [POISON, TOUGH];
const MINDBUG_KEYWORDS_END_TURN = [FRENZY];

interface AddSmashesPowerCard {
    function addSmashesOrder();
    function addSmashes();
}

#[Item('card')]
class PowerCard {
    #[ItemField(kind: ItemFieldKind::ID, dbField: 'card_id')]
    public int $id;
    #[ItemField(kind: ItemFieldKind::LOCATION, dbField: 'card_location')]
    public string $location;
    #[ItemField(kind: ItemFieldKind::LOCATION, dbField: 'card_location_arg', locationIndex: 1)]
    public ?int $location_arg;
    #[ItemField(dbField: 'card_type')]
    public int $type;
    #[ItemField(dbField: 'card_type_arg')]
    public int $type_arg;
    #[ItemField(kind: ItemFieldKind::ORDER)]
    public int $order = 0;

    #[ItemField]
    public ?int $used;
    #[ItemField]
    public ?ActivatedConsumableKeyword $activated;
    
    public ?int $mimicType = null;
    public int $tokens = 0;
    public int $side = 0; // 0 front, 1 back

    public ?array $mindbugKeywords = null;
    public ?int $mimickingCardId = null;
    public ?int $mimickingTileId = null;

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
