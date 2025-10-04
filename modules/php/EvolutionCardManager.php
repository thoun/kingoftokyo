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
    // Space Penguin
    DEEP_DIVE_EVOLUTION => 'DeepDive',
    BLIZZARD_EVOLUTION => 'Blizzard',
    ICY_REFLECTION_EVOLUTION => 'IcyReflection',

    // Cyber Kitty
    NINE_LIVES_EVOLUTION => 'NineLives',
    MEGA_PURR_EVOLUTION => 'MegaPurr',
    ELECTRO_SCRATCH_EVOLUTION => 'ElectroScratch',
    FELINE_MOTOR_EVOLUTION => 'FelineMotor',
    // The King
    JUNGLE_FRENZY_EVOLUTION => 'JungleFrenzy',
    MONKEY_RUSH_EVOLUTION => 'MonkeyRush',
    SIMIAN_SCAMPER_EVOLUTION => 'SimianScamper',
    GIANT_BANANA_EVOLUTION => 'GiantBanana',
    TWAS_BEAUTY_KILLED_THE_BEAST_EVOLUTION => 'TwasBeautyKilledTheBeast',

    // Gigazaur
    DETACHABLE_TAIL_EVOLUTION => 'DetachableTail',
    RADIOACTIVE_WASTE_EVOLUTION => 'RadioactiveWaste',
    PRIMAL_BELLOW_EVOLUTION => 'PrimalBellow',
    SAURIAN_ADAPTABILITY_EVOLUTION => 'SaurianAdaptability',

    // Meka Dragon
    DESTRUCTIVE_ANALYSIS_EVOLUTION => 'DestructiveAnalysis',
    TUNE_UP_EVOLUTION => 'TuneUp',
    LIGHTNING_ARMOR_EVOLUTION => 'LightningArmor',
    TARGET_ACQUIRED_EVOLUTION => 'TargetAcquired',

    // Boogie Woogie
    WELL_OF_SHADOW_EVOLUTION => 'WellOfShadow',
    WORM_INVADERS_EVOLUTION => 'WormInvaders',

    // Alienoid
    ALIEN_SCOURGE_EVOLUTION => 'AlienScourge',
    PRECISION_FIELD_SUPPORT_EVOLUTION => 'PrecisionFieldSupport',
    ANGER_BATTERIES_EVOLUTION => 'AngerBatteries',
    ADAPTING_TECHNOLOGY_EVOLUTION => 'AdaptingTechnology',
    SUPERIOR_ALIEN_TECHNOLOGY_EVOLUTION => 'SuperiorAlienTechnology',

    // Boogie Woogie
    WORST_NIGHTMARE_EVOLUTION => 'WorstNightmare',

    // Pumpkin Jack
    SMASHING_PUMPKIN_EVOLUTION => 'SmashingPumpkin',
    TRICK_OR_THREAT_EVOLUTION => 'TrickOrThreat',
    BOBBING_FOR_APPLES_EVOLUTION => 'BobbingForApples',
    FEAST_OF_CROWS_EVOLUTION => 'FeastOfCrows',
    SCYTHE_EVOLUTION => 'Scythe',
    CANDY_EVOLUTION => 'Candy',

    // Pandakaï
    PANDA_MONIUM_EVOLUTION => 'PandaMonium',
    EATS_SHOOTS_AND_LEAVES_EVOLUTION => 'EatsShootsAndLeaves',
    BAMBOOZLE_EVOLUTION => 'Bamboozle',
    BEAR_NECESSITIES_EVOLUTION => 'BearNecessities',
    YIN_YANG_EVOLUTION => 'YinYang',

    // cyberbunny
    STROKE_OF_GENIUS_EVOLUTION => 'StrokeOfGenius',
    EMERGENCY_BATTERY_EVOLUTION => 'EmergencyBattery',
    RABBIT_S_FOOT_EVOLUTION => 'RabbitSFoot',
    ELECTRIC_CARROT_EVOLUTION => 'ElectricCarrot',

    // kraken
    HEALING_RAIN_EVOLUTION => 'HealingRain',
    DESTRUCTIVE_WAVE_EVOLUTION => 'DestructiveWave',
    CULT_WORSHIPPERS_EVOLUTION => 'CultWorshippers',

    // Baby Gigazaur
    MY_TOY_EVOLUTION => 'MyToy',
    NURTURE_THE_YOUNG_EVOLUTION => 'NurtureTheYoung',
    YUMMY_YUMMY_EVOLUTION => 'YummyYummy',

    // MasterMindbug
    MINDBUG_ACQUISITION_EVOLUTION => 'MindbugAcquisition',
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
