<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo;

require_once(__DIR__.'/framework-prototype/item/item.php');
require_once(__DIR__.'/framework-prototype/item/item-field.php');
require_once(__DIR__.'/framework-prototype/item/item-location.php');
require_once(__DIR__.'/framework-prototype/item/item-manager.php');

use Bga\GameFrameworkPrototype\Item\ItemLocation;
use \Bga\GameFrameworkPrototype\Item\ItemManager;
use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;

const EVOLUTION_CARD_CLASSES = [
    MEGA_PURR_EVOLUTION => 'MegaPurr',
    JUNGLE_FRENZY_EVOLUTION => 'JungleFrenzy',
];

class EvolutionCardManager extends ItemManager {

    function __construct(
        protected Game $game,
    ) {
        parent::__construct(
            EvolutionCard::class,
            [
                new ItemLocation('deck', true, autoReshuffleFrom: 'discard'),
            ],
        );
    }

    function setup(array $affectedPlayersMonsters):void {
        foreach($this->game->MONSTERS_WITH_POWER_UP_CARDS as $monster) {
            $cards = [];
            $location = array_key_exists($monster, $affectedPlayersMonsters) ? 'deck'.$affectedPlayersMonsters[$monster] : 'monster'.$monster;
            for($card=1; $card<=8; $card++) {
                $type = $monster * 10 + $card;
                $cards[] = ['location' => $location, 'type' => $type, 'type_arg' => 0, 'nbr' => 1];
            }
            $this->createItems($cards);
            $this->shuffle($location); 
        }

        if (count($affectedPlayersMonsters) > 0) {
            $this->game->setOwnerIdForAllEvolutions();
        }
    }

    protected function getClassName(?array $dbItem): ?string {
        $cardType = intval($dbItem['card_type']);
        if (!array_key_exists($cardType, EVOLUTION_CARD_CLASSES)) {
            return null;
            //throw new \BgaSystemException('Unexisting EvolutionCard class');
        }

        $className = EvolutionCard::class;
        $namespace = substr($className, 0, strrpos($className, '\\'));
        return $namespace . '\\' . EVOLUTION_CARD_CLASSES[$cardType];
    }
/*
    public function getTable(?int $level = null): array {
        return $this->getItemsInLocation('table', $level);
    }

    public function getPlayerTiles(int $playerId): array {
        return $this->getItemsInLocation('hand', $playerId);
    }*/

    public function immediateEffect(EvolutionCard $card, Context $context) {
        if (method_exists($card, 'immediateEffect')) {
            /** @disregard */
            return $card->immediateEffect($context);
        }
    }

}
