<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo;

require_once(__DIR__.'/framework-prototype/item/item.php');
require_once(__DIR__.'/framework-prototype/item/item-field.php');
require_once(__DIR__.'/framework-prototype/item/item-location.php');
require_once(__DIR__.'/framework-prototype/item/item-manager.php');

use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\GameFrameworkPrototype\Item\ItemLocation;
use \Bga\GameFrameworkPrototype\Item\ItemManager;
use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\ActivatedConsumableKeyword;
use Bga\Games\KingOfTokyo\Objects\Context;

const EVOLUTION_CARD_CLASSES = [
    // Space Penguin
    FREEZE_RAY_EVOLUTION => 'FreezeRay',
    MIRACULOUS_CATCH_EVOLUTION => 'MiraculousCatch',
    DEEP_DIVE_EVOLUTION => 'DeepDive',
    COLD_WAVE_EVOLUTION => 'ColdWave',
    ENCASED_IN_ICE_EVOLUTION => 'EncasedInIce',
    BLIZZARD_EVOLUTION => 'Blizzard',
    BLACK_DIAMOND_EVOLUTION => 'BlackDiamond',
    ICY_REFLECTION_EVOLUTION => 'IcyReflection',
    // Alienoid
    ALIEN_SCOURGE_EVOLUTION => 'AlienScourge',
    PRECISION_FIELD_SUPPORT_EVOLUTION => 'PrecisionFieldSupport',
    ANGER_BATTERIES_EVOLUTION => 'AngerBatteries',
    ADAPTING_TECHNOLOGY_EVOLUTION => 'AdaptingTechnology',
    FUNNY_LOOKING_BUT_DANGEROUS_EVOLUTION => 'FunnyLookingButDangerous',
    EXOTIC_ARMS_EVOLUTION => 'ExoticArms',
    MOTHERSHIP_SUPPORT_EVOLUTION => 'MothershipSupport',
    SUPERIOR_ALIEN_TECHNOLOGY_EVOLUTION => 'SuperiorAlienTechnology',
    // Cyber Kitty
    NINE_LIVES_EVOLUTION => 'NineLives',
    MEGA_PURR_EVOLUTION => 'MegaPurr',
    ELECTRO_SCRATCH_EVOLUTION => 'ElectroScratch',
    CAT_NIP_EVOLUTION => 'CatNip',
    PLAY_WITH_YOUR_FOOD_EVOLUTION => 'PlayWithYourFood',
    FELINE_MOTOR_EVOLUTION => 'FelineMotor',
    MOUSE_HUNTER_EVOLUTION => 'MouseHunter',
    MEOW_MISSLE_EVOLUTION => 'MeowMissle',
    // The King
    MONKEY_RUSH_EVOLUTION => 'MonkeyRush',
    SIMIAN_SCAMPER_EVOLUTION => 'SimianScamper',
    JUNGLE_FRENZY_EVOLUTION => 'JungleFrenzy',
    GIANT_BANANA_EVOLUTION => 'GiantBanana',
    CHEST_THUMPING_EVOLUTION => 'ChestThumping',
    ALPHA_MALE_EVOLUTION => 'AlphaMale',
    I_AM_THE_KING_EVOLUTION => 'IAmTheKing',
    TWAS_BEAUTY_KILLED_THE_BEAST_EVOLUTION => 'TwasBeautyKilledTheBeast',
    // Gigazaur
    DETACHABLE_TAIL_EVOLUTION => 'DetachableTail',
    RADIOACTIVE_WASTE_EVOLUTION => 'RadioactiveWaste',
    PRIMAL_BELLOW_EVOLUTION => 'PrimalBellow',
    SAURIAN_ADAPTABILITY_EVOLUTION => 'SaurianAdaptability',
    DEFENDER_OF_TOKYO_EVOLUTION => 'DefenderOfTokyo',
    HEAT_VISION_EVOLUTION => 'HeatVision',
    GAMMA_BREATH_EVOLUTION => 'GammaBreath',
    TAIL_SWEEP_EVOLUTION => 'TailSweep',
    // Meka Dragon
    MECHA_BLAST_EVOLUTION => 'MechaBlast',
    DESTRUCTIVE_ANALYSIS_EVOLUTION => 'DestructiveAnalysis',
    PROGRAMMED_TO_DESTROY_EVOLUTION => 'ProgrammedToDestroy',
    TUNE_UP_EVOLUTION => 'TuneUp',
    BREATH_OF_DOOM_EVOLUTION => 'BreathOfDoom',
    LIGHTNING_ARMOR_EVOLUTION => 'LightningArmor',
    CLAWS_OF_STEEL_EVOLUTION => 'ClawsOfSteel',
    TARGET_ACQUIRED_EVOLUTION => 'TargetAcquired',
    // Boogie Woogie
    BOO_EVOLUTION => 'Boo',
    WORST_NIGHTMARE_EVOLUTION => 'WorstNightmare',
    I_LIVE_UNDER_YOUR_BED_EVOLUTION => 'ILiveUnderYourBed',
    BOOGIE_DANCE_EVOLUTION => 'BoogieDance',
    WELL_OF_SHADOW_EVOLUTION => 'WellOfShadow',
    WORM_INVADERS_EVOLUTION => 'WormInvaders',
    NIGHTLIFE_EVOLUTION => 'Nightlife',
    DUSK_RITUAL_EVOLUTION => 'DuskRitual',
    // Pumpkin Jack
    DETACHABLE_HEAD_EVOLUTION => 'DetachableHead',
    IGNIS_FATUS_EVOLUTION => 'IgnisFatus',
    SMASHING_PUMPKIN_EVOLUTION => 'SmashingPumpkin',
    TRICK_OR_THREAT_EVOLUTION => 'TrickOrThreat',
    BOBBING_FOR_APPLES_EVOLUTION => 'BobbingForApples',
    FEAST_OF_CROWS_EVOLUTION => 'FeastOfCrows',
    SCYTHE_EVOLUTION => 'Scythe',
    CANDY_EVOLUTION => 'Candy',
    // King Kong
    SON_OF_KONG_KIKO_EVOLUTION => 'SonOfKongKiko',
    KING_OF_SKULL_ISLAND_EVOLUTION => 'KingOfSkullIsland',
    ISLANDER_SACRIFICE_EVOLUTION => 'IslanderSacrifice',
    MONKEY_LEAP_EVOLUTION => 'MonkeyLeap',
    IT_WAS_BEAUTY_KILLED_THE_BEAST_EVOLUTION => 'ItWasBeautyKilledTheBeast',
    JET_CLUB_EVOLUTION => 'JetClub',
    EIGHTH_WONDER_OF_THE_WORLD_EVOLUTION => 'EighthWonderOfTheWorld',
    CLIMB_TOKYO_TOWER_EVOLUTION => 'ClimbTokyoTower',
    // Pandakaï
    PANDA_MONIUM_EVOLUTION => 'PandaMonium',
    EATS_SHOOTS_AND_LEAVES_EVOLUTION => 'EatsShootsAndLeaves',
    BAMBOOZLE_EVOLUTION => 'Bamboozle',
    BEAR_NECESSITIES_EVOLUTION => 'BearNecessities',
    PANDA_EXPRESS_EVOLUTION => 'PandaExpress',
    BAMBOO_SUPPLY_EVOLUTION => 'BambooSupply',
    PANDARWINISM_EVOLUTION => 'Pandarwinism',
    YIN_YANG_EVOLUTION => 'YinYang',
    // Cyber Bunny
    STROKE_OF_GENIUS_EVOLUTION => 'StrokeOfGenius',
    EMERGENCY_BATTERY_EVOLUTION => 'EmergencyBattery',
    RABBIT_S_FOOT_EVOLUTION => 'RabbitSFoot',
    HEART_OF_THE_RABBIT_EVOLUTION => 'HeartOfTheRabbit',
    SECRET_LABORATORY_EVOLUTION => 'SecretLaboratory',
    KING_OF_THE_GIZMO_EVOLUTION => 'KingOfTheGizmo',
    ENERGY_SWORD_EVOLUTION => 'EnergySword',
    ELECTRIC_CARROT_EVOLUTION => 'ElectricCarrot',
    // Kraken
    HEALING_RAIN_EVOLUTION => 'HealingRain',
    DESTRUCTIVE_WAVE_EVOLUTION => 'DestructiveWave',
    CULT_WORSHIPPERS_EVOLUTION => 'CultWorshippers',
    HIGH_TIDE_EVOLUTION => 'HighTide',
    TERROR_OF_THE_DEEP_EVOLUTION => 'TerrorOfTheDeep',
    EATER_OF_SOULS_EVOLUTION => 'EaterOfSouls',
    SUNKEN_TEMPLE_EVOLUTION => 'SunkenTemple',
    MANDIBLES_OF_DREAD_EVOLUTION => 'MandiblesOfDread',
    // Baby Gigazaur
    MY_TOY_EVOLUTION => 'MyToy',
    GROWING_FAST_EVOLUTION => 'GrowingFast',
    NURTURE_THE_YOUNG_EVOLUTION => 'NurtureTheYoung',
    TINY_TAIL_EVOLUTION => 'TinyTail',
    TOO_CUTE_TO_SMASH_EVOLUTION => 'TooCuteToSmash',
    SO_SMALL_EVOLUTION => 'SoSmall',
    UNDERRATED_EVOLUTION => 'Underrated',
    YUMMY_YUMMY_EVOLUTION => 'YummyYummy',
    // Gigasnail Hydra
    UNSTOPPABLE_HYDRA_EVOLUTION_1 => 'UnstoppableHydra',
    UNSTOPPABLE_HYDRA_EVOLUTION_2 => 'UnstoppableHydra',
    ENERGY_INFUSED_MONSTER_EVOLUTION => 'EnergyInfusedMonster',
    THREE_TIMES_AS_STURDY_EVOLUTION => 'ThreeTimesAsSturdy',
    SCARY_FACE_EVOLUTION => 'ScaryFace',
    THREE_TIMES_AS_STRONG_EVOLUTION => 'ThreeTimesAsStrong',
    THINKING_FACE_EVOLUTION => 'ThinkingFace',
    HUNGRY_FACE_EVOLUTION => 'HungryFace',
    // MasterMindbug
    MINDBUG_ACQUISITION_EVOLUTION => 'MindbugAcquisition',
    INTERGALACTIC_GENIUS_EVOLUTION => 'IntergalacticGenius',
    SUPERIOR_BRAIN_EVOLUTION => 'SuperiorBrain',
    INTERDIMENSIONAL_PORTAL_EVOLUTION => 'InterdimensionalPortal',
    HELPFUL_MINDBUG_EVOLUTION => 'HelpfulMindbug',
    MINDBUGS_OVERLORD_EVOLUTION => 'MindbugsOverlord',
    MIND_CONTROL_EVOLUTION => 'MindControl',
    NEUTRALIZING_LOOK_EVOLUTION => 'NeutralizingLook',
    // Sharky Crab-dog Mummypus-Zilla
    SHARK_ATTACK_EVOLUTION => 'SharkAttack',
    ENERGY_DEVOURER_EVOLUTION => 'EnergyDevourer',
    STRANGE_EVOLUTION_EVOLUTION => 'StrangeEvolution',
    CRAB_CLAW_EVOLUTION => 'CrabClaw',
    UNDEAD_MUMMY_EVOLUTION => 'UndeadMummy',
    FOLLOW_THE_CUBES_EVOLUTION => 'FollowTheCubes',
    POISONED_TENTACLES_EVOLUTION => 'PoisonedTentacles',
    CHEW_PINCH_CATCH_AND_SMACK_EVOLUTION => 'ChewPinchCatchAndSmack',
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
        foreach($this->game->powerUpExpansion->getMonstersWithPowerUpCards() as $monster) {
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


    /**
     * @return EvolutionCard[]
     */
    function getPlayerRealByLocation(int $playerId, string $location): array {        
        $evolutions = $this->getItemsInLocation($location, $playerId, true, sortByField: 'location_arg');
        return $evolutions;
    }

    /**
     * Returns all real evolutions the player have.
     * Includes disabled Permanent evolutions.
     * 
     * @return EvolutionCard[]
     */
    function getPlayerReal(int $playerId, bool $fromTable, bool $fromHand): array {
        if (!$this->game->powerUpExpansion->isActive()) {
            return [];
        }

        $evolutions = [
            ...($fromTable ? $this->getPlayerRealByLocation($playerId, 'table') : []),
            ...($fromHand ? $this->getPlayerRealByLocation($playerId, 'hand') : []),
        ];
        return $evolutions;
    }

    /**
     * Returns all virtual evolutions the player have (an evolution copied with Icy Reflection will be returned as an Evolution).
     * A virtual card will have a negative id.
     * Excludes disabled Permanent evolutions.
     * 
     * @return EvolutionCard[]
     */
    function getPlayerVirtual(int $playerId, bool $fromTable, bool $fromHand, bool $virtualFirst = false): array {
        $evolutions = $this->getPlayerReal($playerId, $fromTable, $fromHand);
        if (!$this->game->keepAndEvolutionCardsHaveEffect()) {
            $evolutions = Arrays::filter($evolutions, fn($evolution) => $this->game->EVOLUTION_CARDS_TYPES[$evolution->type] != 1);
        }

        $icyReflectionEvolution = Arrays::find($evolutions, fn($evolution) => $evolution->type === ICY_REFLECTION_EVOLUTION);
        if ($icyReflectionEvolution) {
            $mimickedCardId = $this->game->getMimickedEvolutionId();
            if ($mimickedCardId) {
                $virtualCard = $this->getItemById($mimickedCardId);
                if ($virtualCard) {
                    $virtualCard->id = -$virtualCard->id;
                    $virtualCard->mimickingEvolutionId = $icyReflectionEvolution->id;
                    if ($virtualFirst) {
                        array_unshift($evolutions, $virtualCard);
                    } else {
                        $evolutions[] = $virtualCard;
                    }
                }
            }
        }

        return $evolutions;
    }

    /**
     * Returns all virtual evolutions the player have (an evolution copied with Icy Reflection will be returned as an Evolution) of a specified type.
     * A virtual card will have a negative id.
     * Excludes disabled Permanent evolutions.
     * 
     * @return EvolutionCard[]
     */
    function getPlayerVirtualByType(int $playerId, int $type, bool $fromTable, bool $fromHand, bool $virtualFirst = false): array {
        $evolutions = $this->getPlayerVirtual($playerId, $fromTable, $fromHand, $virtualFirst);
        return Arrays::filter($evolutions, fn($evolution) => $evolution->type === $type);
    }

    /**
     * Returns the searched gift evolution the player have in front of him, only if it really affects him.
     */
    function getGiftEvolutionOfType(int $playerId, int $cardType, bool $virtualFirst = false): ?EvolutionCard {
        $cards = $this->getPlayerVirtualByType($playerId, $cardType, true, false, $virtualFirst);
        $card = count($cards) > 0 ? $cards[0] : null;

        if ($card !== null && $card->ownerId === $playerId) {
            return null; // evolution owner is not affected by gift
        }

        return $card;
    }

    public function immediateEffect(EvolutionCard $card, Context $context) {
        if (method_exists($card, 'immediateEffect')) {
            /** @disregard */
            return $card->immediateEffect($context);
        }
    }

    public function onAddSmashes(Context $context): array {
        $cards = $this->getPlayerVirtual($context->currentPlayerId, true, false);
        $cards = Arrays::filter($cards, fn($card) => method_exists($card, 'addSmashesOrder') && method_exists($card, 'addSmashes'));
        $addedByCards = 0;
        $addingCards = [];

        // to make sure antimatter beam multiplication is done after barbs addition
        usort($cards, 
            // Sort by the return value of addSmashesOrder, smaller order first
            /** @disregard */
            fn($a, $b) => $a->addSmashesOrder() <=> $b->addSmashesOrder()
        );

        foreach ($cards as $card) {
             /** @disregard */
            $addedByCard = $card->addSmashes($context);
            $addedByCards += $addedByCard;
            $context->addedSmashes += $addedByCard;
            if ($addedByCard > 0) {
                $addingCards[] = 3000 + $card->type;
            }
        }

        return [$addedByCards, $addingCards];
    }

    public function activateKeyword(EvolutionCard $card, string $keyword): void {
        $card->activated = new ActivatedConsumableKeyword($keyword);
        $this->updateItem($card, ['activated']);
    }

    public function setActivatedKeywordTarget(EvolutionCard $card, int $targetPlayerId): void {
        $card->activated->targetPlayerId = $targetPlayerId;
        $this->updateItem($card, ['activated']);
    }

}
