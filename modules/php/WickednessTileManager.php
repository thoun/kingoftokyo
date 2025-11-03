<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo;

require_once(__DIR__.'/framework-prototype/item/item.php');
require_once(__DIR__.'/framework-prototype/item/item-field.php');
require_once(__DIR__.'/framework-prototype/item/item-location.php');
require_once(__DIR__.'/framework-prototype/item/item-manager.php');
require_once(__DIR__.'/framework-prototype/item/card-manager.php');

use Bga\GameFrameworkPrototype\Helpers\Arrays;
use \Bga\GameFrameworkPrototype\Item\CardManager;
use Bga\Games\KingOfTokyo\Objects\AddSmashTokens;
use Bga\Games\KingOfTokyo\Objects\Context;
use Bga\Games\KingOfTokyo\WickednessTiles\WickednessTile;

// Orange
const DEVIOUS_WICKEDNESS_TILE = 1;
const ETERNAL_WICKEDNESS_TILE = 2;
const SKULKING_WICKEDNESS_TILE = 3;
const TIRELESS_WICKEDNESS_TILE = 4;
const CYBERBRAIN_WICKEDNESS_TILE = 5;
const EVIL_LAIR_WICKEDNESS_TILE = 6;
const FULL_REGENERATION_WICKEDNESS_TILE = 7;
const WIDESPREAD_PANIC_WICKEDNESS_TILE = 8;
const ANTIMATTER_BEAM_WICKEDNESS_TILE = 9;
const SKYBEAM_WICKEDNESS_TILE = 10;
// Green 
const BARBS_WICKEDNESS_TILE = 101;
const FINAL_ROAR_WICKEDNESS_TILE = 102;
const POISON_SPIT_WICKEDNESS_TILE = 103;
const UNDERDOG_WICKEDNESS_TILE = 104;
const DEFENDER_OF_TOKYO_WICKEDNESS_TILE = 105;
const FLUXLING_WICKEDNESS_TILE = 106;
const HAVE_IT_ALL_WICKEDNESS_TILE = 107;
const SONIC_BOOMER_WICKEDNESS_TILE = 108;
const FINAL_PUSH_WICKEDNESS_TILE = 109; // used on another class
const STARBURST_WICKEDNESS_TILE = 110;

const WICKEDNESS_TILE_CLASSES = [
    // Orange
    DEVIOUS_WICKEDNESS_TILE => 'Devious',
    ETERNAL_WICKEDNESS_TILE => 'Eternal',
    SKULKING_WICKEDNESS_TILE => 'Skulking',
    TIRELESS_WICKEDNESS_TILE => 'Tireless',
    CYBERBRAIN_WICKEDNESS_TILE => 'CyberBrain',
    EVIL_LAIR_WICKEDNESS_TILE => 'EvilLair',
    FULL_REGENERATION_WICKEDNESS_TILE => 'FullRegeneration',
    WIDESPREAD_PANIC_WICKEDNESS_TILE => 'WidespreadPanic',
    ANTIMATTER_BEAM_WICKEDNESS_TILE => 'AntimatterBeam',
    SKYBEAM_WICKEDNESS_TILE => 'SkyBeam',
    // Green 
    BARBS_WICKEDNESS_TILE => 'Barbs',
    FINAL_ROAR_WICKEDNESS_TILE => 'FinalRoar',
    POISON_SPIT_WICKEDNESS_TILE => 'PoisonSpit',
    UNDERDOG_WICKEDNESS_TILE => 'Underdog',
    DEFENDER_OF_TOKYO_WICKEDNESS_TILE => 'DefenderOfTokyo',
    FLUXLING_WICKEDNESS_TILE => 'Fluxling',
    HAVE_IT_ALL_WICKEDNESS_TILE => 'HaveItAll',
    SONIC_BOOMER_WICKEDNESS_TILE => 'SonicBoomer',
    FINAL_PUSH_WICKEDNESS_TILE => 'FinalPush',
    STARBURST_WICKEDNESS_TILE => 'Starburst',
];

class WickednessTileManager extends CardManager {

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
        $this->createCards($cards);

        $allTiles = $this->getCardsInLocation('deck');

        foreach ([3, 6, 10] as $level) {
            $levelTiles = Arrays::filter($allTiles, fn($tile) =>
                $level === (($tile->type % 100) > 8 ? 10 : (($tile->type % 100) > 4 ? 6 : 3))
            );
            $this->moveCards($levelTiles, 'table', $level);
        }
    }

    public function getClassName(?array $dbItem): ?string {
        $cardType = intval($dbItem['card_type']);
        if (!array_key_exists($cardType, WICKEDNESS_TILE_CLASSES)) {
            throw new \BgaSystemException('Unexisting WickednessTile class');
        }

        $className = WickednessTile::class;
        $namespace = substr($className, 0, strrpos($className, '\\'));
        return $namespace . '\\' . WICKEDNESS_TILE_CLASSES[$cardType];
    }

    /**
     * @return WickednessTile[]
     */
    public function getTable(?int $level = null): array {
        return $this->getCardsInLocation('table', $level);
    }

    /**
     * @return WickednessTile[]
     */
    public function getPlayerTiles(int $playerId): array {
        return $this->getCardsInLocation('hand', $playerId);
    }

    public function immediateEffect(WickednessTile $tile, Context $context) {
        if (method_exists($tile, 'immediateEffect')) {
            /** @disregard */
            return $tile->immediateEffect($context);
        }
    }

    public function onStartTurn(Context $context) {
        $tiles = $this->getPlayerTiles($context->currentPlayerId);

        foreach ($tiles as $tile) {
            if (method_exists($tile, 'startTurnEffect')) {
                /** @disregard */
                $tile->startTurnEffect($context);
            }
        }
    }

    public function onIncDieCount(Context $context): int {
        $tiles = $this->getPlayerTiles($context->currentPlayerId);
        $inc = 0;

        foreach ($tiles as $tile) {
            if (method_exists($tile, 'incDieCount')) {
                /** @disregard */
                $inc += $tile->incDieCount($context);
            }
        }

        return $inc;
    }

    public function onIncDieRollCount(Context $context): int {
        $tiles = $this->getPlayerTiles($context->currentPlayerId);
        $inc = 0;

        foreach ($tiles as $tile) {
            if (method_exists($tile, 'incDieRollCount')) {
                /** @disregard */
                $inc += $tile->incDieRollCount($context);
            }
        }

        return $inc;
    }

    public function onIncMaxHealth(Context $context): int {
        $tiles = $this->getPlayerTiles($context->currentPlayerId);
        $inc = 0;

        foreach ($tiles as $tile) {
            if (method_exists($tile, 'incMaxHealth')) {
                /** @disregard */
                $inc += $tile->incMaxHealth($context);
            }
        }

        return $inc;
    }

    public function onResolvingDieSymbol(Context $context) {
        $tiles = $this->getPlayerTiles($context->currentPlayerId);

        foreach ($tiles as $tile) {
            if (method_exists($tile, 'resolvingDiceEffect')) {
                /** @disregard */
                $tile->resolvingDiceEffect($context);
            }
        }
    }

    public function onEnteringTokyo(Context $context) {
        $tiles = $this->getPlayerTiles($context->currentPlayerId);

        foreach ($tiles as $tile) {
            if (method_exists($tile, 'enteringTokyoEffect')) {
                /** @disregard */
                $tile->enteringTokyoEffect($context);
            }
        }
    }

    public function onIncPowerCardsReduction(Context $context): int {
        $tiles = $this->getPlayerTiles($context->currentPlayerId);
        $inc = 0;

        foreach ($tiles as $tile) {
            if (method_exists($tile, 'incPowerCardsReduction')) {
                /** @disregard */
                $inc += $tile->incPowerCardsReduction($context);
            }
        }

        return $inc;
    }

    public function winOnElimination(Context $context): ?WickednessTile {
        $tiles = $this->getPlayerTiles($context->currentPlayerId);

        foreach ($tiles as $tile) {
            if (method_exists($tile, 'winOnElimination')) {
                /** @disregard */
                if ($tile->winOnElimination($context)) {
                    return $tile;
                }
            }
        }

        return null;
    }

    public function onAddSmashes(Context $context): array {
        $tiles = $this->getPlayerTiles($context->currentPlayerId);
        $tiles = Arrays::filter($tiles, fn($tile) => method_exists($tile, 'addSmashes'));
        $addedByTiles = 0;
        $addingTiles = [];

        // to make sure antimatter beam multiplication is done after barbs addition
        /** @var AddSmashesPowerCard[] $tiles */
        usort($tiles, 
            // Sort by the return value of addSmashesOrder, smaller order first
            fn($a, $b) => (method_exists($a, 'addSmashesOrder') ? $a->addSmashesOrder() : 1) <=> (method_exists($b, 'addSmashesOrder') ? $b->addSmashesOrder() : 1)
        );

        foreach ($tiles as $tile) {
            $addedByTile = $tile->addSmashes($context);
            $addedByTiles += $addedByTile;
            $context->addedSmashes += $addedByTile;
            if ($addedByTile > 0) {
                $addingTiles[] = 2000 + $tile->type;
            }
        }

        return [$addedByTiles, $addingTiles];
    }

    public function onAddingSmashTokens(Context $context): AddSmashTokens {
        $tiles = $this->getPlayerTiles($context->currentPlayerId);
        $result = new AddSmashTokens();

        foreach ($tiles as $tile) {
            if (method_exists($tile, 'addSmashTokens')) {
                /** @disregard */
                $result->add($tile->addSmashTokens($context));
            }
        }

        return $result;
    }

    public function onApplyDamage(Context $context) {
        $tiles = $this->getPlayerTiles($context->attackerPlayerId);

        foreach ($tiles as $tile) {
            if (method_exists($tile, 'onApplyDamageEffect')) {
                /** @disregard */
                $tile->onApplyDamageEffect($context);
            }
        }
    }

    public function onBuyCard(Context $context) {
        $tiles = $this->getPlayerTiles($context->currentPlayerId);

        foreach ($tiles as $tile) {
            if (method_exists($tile, 'buyCardEffect')) {
                /** @disregard */
                $tile->buyCardEffect($context);
            }
        }
    }

}
