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
use KOT\Objects\WickednessTile;

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
            $cards[] = ['location' => 'deck', 'type' => $value + 100 * $cardSide, 'tokens' => 0];
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
}
