<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo;

require_once(__DIR__.'/framework-prototype/item/item.php');
require_once(__DIR__.'/framework-prototype/item/item-field.php');
require_once(__DIR__.'/framework-prototype/item/item-location.php');
require_once(__DIR__.'/framework-prototype/item/item-manager.php');

use Bga\GameFrameworkPrototype\Helpers\Arrays;
use \Bga\GameFrameworkPrototype\Item\ItemManager;
use \Bga\GameFrameworkPrototype\Item\ItemLocation;
use Bga\Games\KingOfTokyo\WickednessTiles\WickednessTile;

/*
// wickedness tiles orange
define('DEVIOUS_WICKEDNESS_TILE', 1);
define('ETERNAL_WICKEDNESS_TILE', 2);
define('SKULKING_WICKEDNESS_TILE', 3);
define('TIRELESS_WICKEDNESS_TILE', 4);
define('CYBERBRAIN_WICKEDNESS_TILE', 5);
define('EVIL_LAIR_WICKEDNESS_TILE', 6);
define('FULL_REGENERATION_WICKEDNESS_TILE', 7);
define('WIDESPREAD_PANIC_WICKEDNESS_TILE', 8);
define('ANTIMATTER_BEAM_WICKEDNESS_TILE', 9);
define('SKYBEAM_WICKEDNESS_TILE', 10);
// wickedness tiles green 
define('BARBS_WICKEDNESS_TILE', 101);
define('FINAL_ROAR_WICKEDNESS_TILE', 102);
define('POISON_SPIT_WICKEDNESS_TILE', 103);
define('UNDERDOG_WICKEDNESS_TILE', 104);
define('DEFENDER_OF_TOKYO_WICKEDNESS_TILE', 105);
define('FLUXLING_WICKEDNESS_TILE', 106);
define('HAVE_IT_ALL_WICKEDNESS_TILE', 107);
define('SONIC_BOOMER_WICKEDNESS_TILE', 108);
*/
const EVOLUTION_CLASSES = [
    FINAL_PUSH_WICKEDNESS_TILE => 'FinalPush',
    STARBURST_WICKEDNESS_TILE => 'Starburst',
];

class WickednessTileManager extends ItemManager {

    function __construct(
        protected $game,
    ) {
        parent::__construct(
            WickednessTile::class,
            [],
        );
    }

    function setup(int $side) {
        for($value=1; $value<=10; $value++) { // curse cards
            $cardSide = $side === 4 ? bga_rand(0, 1) : $side - 2;
            $level = $value > 8 ? 10 : ($value > 4 ? 6 : 3);
            $cards[] = ['location' => 'table', 'location_arg' => $level, 'type' => $value + 100 * $cardSide, 'tokens' => 0];
        }
        $this->createItems($cards);

        $allTiles = $this->getItemsInLocation('deck');

        foreach ([3, 6, 10] as $level) {
            $levelTiles = Arrays::filter($allTiles, fn($tile) =>
                $level === (($tile->type % 100) > 8 ? 10 : (($tile->type % 100) > 4 ? 6 : 3))
            );
            $this->moveItems($levelTiles, 'table', $level);
        }
    }

    protected function getClassName(?array $dbItem): ?string {
        $cardType = intval($dbItem['card_type']);
        if (array_key_exists($cardType, EVOLUTION_CLASSES)) {
            $className = WickednessTile::class;
            $namespace = substr($className, 0, strrpos($className, '\\'));
            return $namespace . '\\' . EVOLUTION_CLASSES[$cardType];
        }
        return null;
    }
}
