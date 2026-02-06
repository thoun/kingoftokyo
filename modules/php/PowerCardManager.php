<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo;

require_once(__DIR__.'/framework-prototype/item/item.php');
require_once(__DIR__.'/framework-prototype/item/item-field.php');
require_once(__DIR__.'/framework-prototype/item/item-location.php');
require_once(__DIR__.'/framework-prototype/item/item-manager.php');
require_once(__DIR__.'/framework-prototype/item/card-manager.php');

use Bga\GameFramework\NotificationMessage;
use Bga\GameFramework\UserException;
use Bga\GameFramework\VisibleSystemException;
use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\GameFrameworkPrototype\Item\ItemLocation;
use \Bga\GameFrameworkPrototype\Item\CardManager;
use Bga\Games\KingOfTokyo\Objects\ActivatedConsumableKeyword;
use Bga\Games\KingOfTokyo\Objects\Context;
use Bga\Games\KingOfTokyo\PowerCards\PowerCard;

const POWER_CARD_CLASSES = [
    // KEEP
    ACID_ATTACK_CARD => 'AcidAttack',
    ALIEN_ORIGIN_CARD => 'AlienOrigin',
    ALPHA_MONSTER_CARD => 'AlphaMonster',
    ARMOR_PLATING_CARD => 'ArmorPlating',
    BACKGROUND_DWELLER_CARD => 'BackgroundDweller',
    BURROWING_CARD => 'Burrowing',
    CAMOUFLAGE_CARD => 'Camouflage',
    COMPLETE_DESTRUCTION_CARD => 'CompleteDestruction',
    MEDIA_FRIENDLY_CARD => 'MediaFriendly',
    EATER_OF_THE_DEAD_CARD => 'EaterOfTheDead',
    ENERGY_HOARDER_CARD => 'EnergyHoarder',
    EVEN_BIGGER_CARD => 'EvenBigger',
    EXTRA_HEAD_1_CARD => 'ExtraHead',
    EXTRA_HEAD_2_CARD => 'ExtraHead',
    FIRE_BREATHING_CARD => 'FireBreathing',
    FREEZE_TIME_CARD => 'FreezeTime',
    FRIEND_OF_CHILDREN_CARD => 'FriendOfChildren',
    GIANT_BRAIN_CARD => 'GiantBrain',
    GOURMET_CARD => 'Gourmet',
    HEALING_RAY_CARD => 'HealingRay',
    HERBIVORE_CARD => 'Herbivore',
    HERD_CULLER_CARD => 'HerdCuller',
    IT_HAS_A_CHILD_CARD => 'ItHasAChild',
    JETS_CARD => 'Jets',
    MADE_IN_A_LAB_CARD => 'MadeInALab',
    METAMORPH_CARD => 'Metamorph',
    MIMIC_CARD => 'Mimic',
    BATTERY_MONSTER_CARD => 'BatteryMonster',
    NOVA_BREATH_CARD => 'NovaBreath',
    DETRITIVORE_CARD => 'Detritivore',
    OPPORTUNIST_CARD => 'Opportunist',
    PARASITIC_TENTACLES_CARD => 'ParasiticTentacles',
    PLOT_TWIST_CARD => 'PlotTwist',
    POISON_QUILLS_CARD => 'PoisonQuills',
    POISON_SPIT_CARD => 'PoisonSpit',
    PSYCHIC_PROBE_CARD => 'PsychicProbe',
    RAPID_HEALING_CARD => 'RapidHealing',
    REGENERATION_CARD => 'Regeneration',
    ROOTING_FOR_THE_UNDERDOG_CARD => 'RootingForTheUnderdog',
    SHRINK_RAY_CARD => 'ShrinkRay',
    SMOKE_CLOUD_CARD => 'SmokeCloud',
    SOLAR_POWERED_CARD => 'SolarPowered',
    SPIKED_TAIL_CARD => 'SpikedTail',
    STRETCHY_CARD => 'Stretchy',
    ENERGY_DRINK_CARD => 'EnergyDrink',
    URBAVORE_CARD => 'Urbavore',
    WE_RE_ONLY_MAKING_IT_STRONGER_CARD => 'WeReOnlyMakingItStronger',
    WINGS_CARD => 'Wings',
    HIBERNATION_CARD => 'Hibernation',
    NANOBOTS_CARD => 'Nanobots',
    NATURAL_SELECTION_CARD => 'NaturalSelection',
    REFLECTIVE_HIDE_CARD => 'ReflectiveHide',
    SUPER_JUMP_CARD => 'SuperJump',
    UNSTABLE_DNA_CARD => 'UnstableDna',
    ZOMBIFY_CARD => 'Zombify',
    BIOFUEL_CARD => 'Biofuel',
    DRAINING_RAY_CARD => 'DrainingRay',
    ELECTRIC_ARMOR_CARD => 'ElectricArmor',
    FLAMING_AURA_CARD => 'FlamingAura',
    GAMMA_BLAST_CARD => 'GammaBlast',
    HUNGRY_URBAVORE_CARD => 'HungryUrbavore',
    JAGGED_TACTICIAN_CARD => 'JaggedTactician',
    ORB_OF_DOM_CARD => 'OrbOfDom',
    SCAVENGER_CARD => 'Scavenger',
    SHRINKY_CARD => 'Shrinky',
    BULL_HEADED_CARD => 'BullHeaded',
    FREE_WILL_CARD => 'FreeWill',
    EVASIVE_MINDBUG_CARD => 'EvasiveMindbug',
    NO_BRAIN_CARD => 'NoBrain',
    // DISCARD
    APPARTMENT_BUILDING_CARD => 'AppartmentBuilding',
    COMMUTER_TRAIN_CARD => 'CommuterTrain',
    CORNER_STORE_CARD => 'CornerStore',
    DEATH_FROM_ABOVE_CARD => 'DeathFromAbove',
    ENERGIZE_CARD => 'Energize',
    EVACUATION_ORDER_1_CARD => 'EvacuationOrder',
    EVACUATION_ORDER_2_CARD => 'EvacuationOrder',
    FLAME_THROWER_CARD => 'FlameThrower',
    FRENZY_CARD => 'Frenzy',
    GAS_REFINERY_CARD => 'GasRefinery',
    HEAL_CARD => 'Heal',
    HIGH_ALTITUDE_BOMBING_CARD => 'HighAltitudeBombing',
    JET_FIGHTERS_CARD => 'JetFighters',
    NATIONAL_GUARD_CARD => 'NationalGuard',
    NUCLEAR_POWER_PLANT_CARD => 'NuclearPowerPlant',
    SKYSCRAPER_CARD => 'SkyScraper',
    TANK_CARD => 'Tank',
    VAST_STORM_CARD => 'VastStorm',
    MONSTER_PETS_CARD => 'MonsterPets',
    BARRICADES_CARD => 'Barricades',
    ICE_CREAM_TRUCK_CARD => 'IceCreamTruck',
    SUPERTOWER_CARD => 'Supertower',
    MINDBUG_CARD => 'Mindbug',
    DYSFUNCTIONAL_MINDBUG_CARD => 'DysfunctionalMindbug',
    TREASURE_CARD => 'Treasure',
    MIRACULOUS_MINDBUG_CARD => 'MiraculousMindbug',
    // COSTUME
    ASTRONAUT_CARD => 'Astronaut',
    GHOST_CARD => 'Ghost',
    VAMPIRE_CARD => 'Vampire',
    WITCH_CARD => 'Witch',
    DEVIL_CARD => 'Devil',
    PIRATE_CARD => 'Pirate',
    PRINCESS_CARD => 'Princess',
    ZOMBIE_CARD => 'Zombie',
    CHEERLEADER_CARD => 'Cheerleader',
    ROBOT_CARD => 'Robot',
    STATUE_OF_LIBERTY_CARD => 'StatueOfLiberty',
    CLOWN_CARD => 'Clown',
    // CONSUMABLE
    OVEREQUIPPED_TRAPPER_CARD => 'OverequippedTrapper',
    LEGENDARY_HUNTER_CARD => 'LegendaryHunter',
    UNRELIABLE_TARGETING_CARD => 'UnreliableTargeting',
    SNEAKY_ALLOY_CARD => 'SneakyAlloy',
    OFFENSIVE_PROTOCOL_CARD => 'OffensiveProtocol',
    ARCANE_SCEPTER_CARD => 'ArcaneScepter',
    ENERGY_ARMOR_CARD => 'EnergyArmor',
    STRANGE_DESIGN_CARD => 'StrangeDesign',
    ANCESTRAL_DEFENSE_CARD => 'AncestralDefense',
    TOXIC_PETALS_CARD => 'ToxicPetals',
    EXPLOSIVE_CRYSTALS_CARD => 'ExplosiveCrystals',
    ELECTRO_WHIP_CARD => 'ElectroWhip',
    BOLD_MANEUVER_CARD => 'BoldManeuver',
    UNFAIR_GIFT_CARD => 'UnfairGift',
    MAXIMUM_EFFORT_CARD => 'MaximumEffort',
    DEADLY_SHELL_CARD => 'DeadlyShell',
    SPATIAL_HUNTER_CARD => 'SpatialHunter',
];

class PowerCardManager extends CardManager {
    static array $ORIGINS_CARDS_EXCLUSIVE_KEEP_CARDS_LIST = [
        BIOFUEL_CARD,
        DRAINING_RAY_CARD,
        ELECTRIC_ARMOR_CARD,
        FLAMING_AURA_CARD,
        GAMMA_BLAST_CARD,
        HUNGRY_URBAVORE_CARD,
        JAGGED_TACTICIAN_CARD,
        ORB_OF_DOM_CARD,
        SCAVENGER_CARD,
        SHRINKY_CARD,
        BULL_HEADED_CARD,
    ];

    static array $ORIGINS_CARDS_EXCLUSIVE_DISCARD_CARDS_LIST = [
        BARRICADES_CARD,
        ICE_CREAM_TRUCK_CARD,
        SUPERTOWER_CARD,
    ];

    static array $KEEP_CARDS_LIST;
    static array $DISCARD_CARDS_LIST;

    static array $MINDBUG_CARDS_KEEP_CARDS_LIST = [
        FREE_WILL_CARD,
        EVASIVE_MINDBUG_CARD,
        NO_BRAIN_CARD,
    ];

    static array $MINDBUG_CARDS_DISCARD_CARDS_LIST = [
        MINDBUG_CARD,
        DYSFUNCTIONAL_MINDBUG_CARD,
        TREASURE_CARD,
        MIRACULOUS_MINDBUG_CARD,
    ];

    static array $MINDBUG_CARDS_CONSUMABLE_CARDS_LIST = [
        OVEREQUIPPED_TRAPPER_CARD,
        LEGENDARY_HUNTER_CARD,
        UNRELIABLE_TARGETING_CARD,
        SNEAKY_ALLOY_CARD,
        OFFENSIVE_PROTOCOL_CARD,
        ARCANE_SCEPTER_CARD,
        ENERGY_ARMOR_CARD,
        STRANGE_DESIGN_CARD,
        ANCESTRAL_DEFENSE_CARD,
        TOXIC_PETALS_CARD,
        EXPLOSIVE_CRYSTALS_CARD,
        ELECTRO_WHIP_CARD,
        BOLD_MANEUVER_CARD,
        UNFAIR_GIFT_CARD,
        MAXIMUM_EFFORT_CARD,
        DEADLY_SHELL_CARD,
        SPATIAL_HUNTER_CARD,
    ];

    static array $CARD_COST = [
        // KEEP
        1 => 6,
        2 => 3,
        3 => 5,
        4 => 4,
        5 => 4,
        6 => 5,
        7 => 3,
        8 => 3,
        9 => 3,
        10 => 4,
        11 => 3,
        12 => 4,
        13 => 7, 14 => 7,
        15 => 4,
        16 => 5,
        17 => 3,
        18 => 5,
        19 => 4,
        20 => 4,
        21 => 5,
        22 => 3,
        23 => 7,
        24 => 5,
        25 => 2,
        26 => 3,
        27 => 8,
        28 => 3,
        29 => 7,
        30 => 4,
        31 => 3,
        32 => 4,
        33 => 3,
        34 => 3,
        35 => 4,
        36 => 3,
        37 => 3,
        38 => 4,
        39 => 3,
        40 => 6,
        41 => 4,
        42 => 2,
        43 => 5,
        44 => 3,
        45 => 4,
        46 => 4,
        47 => 3,
        48 => 6,
        49 => 4,
        50 => 3,
        51 => 2,
        52 => 6,
        53 => 4,
        54 => 3,
        55 => 4,
        56 => 4,
        57 => 5,
        58 => 5,
        59 => 5,
        60 => 4, 
        61 => 4,
        62 => 3,
        63 => 9,
        64 => 3,
        65 => 4,
        66 => 3,
        FREE_WILL_CARD => 6,
        EVASIVE_MINDBUG_CARD => 3,
        NO_BRAIN_CARD => 3,

        // DISCARD
        101 => 5,
        102 => 4,
        103 => 3,
        104 => 5,
        105 => 8,
        106 => 7, 107 => 7,
        108 => 3,
        109 => 7,
        110 => 6,
        111 => 3,
        112 => 4,
        113 => 5,
        114 => 3,
        115 => 6,
        116 => 6,
        117 => 4,
        118 => 6,
        119 => 0,
        120 => 5,
        121 => 4,
        122 => 7,
        MINDBUG_CARD => 7,
        DYSFUNCTIONAL_MINDBUG_CARD => 3,
        TREASURE_CARD => 3,
        MIRACULOUS_MINDBUG_CARD => 4,

        // COSTUME
        201 => 4,
        202 => 4,
        203 => 3,
        204 => 4,
        205 => 3,
        206 => 4,
        207 => 5,
        208 => 4,
        209 => 3,
        210 => 4,
        211 => 4,
        212 => 3,

        // CONSUMABLE
        OVEREQUIPPED_TRAPPER_CARD => 3,
        LEGENDARY_HUNTER_CARD => 4,
        UNRELIABLE_TARGETING_CARD => 2,
        SNEAKY_ALLOY_CARD => 5,
        OFFENSIVE_PROTOCOL_CARD => 6,
        ARCANE_SCEPTER_CARD => 7,
        ENERGY_ARMOR_CARD => 6,
        STRANGE_DESIGN_CARD => 3,
        ANCESTRAL_DEFENSE_CARD => 4,
        TOXIC_PETALS_CARD => 5,
        EXPLOSIVE_CRYSTALS_CARD => 4,
        ELECTRO_WHIP_CARD => 4,
        BOLD_MANEUVER_CARD => 4,
        UNFAIR_GIFT_CARD => 4,
        MAXIMUM_EFFORT_CARD => 4,
        DEADLY_SHELL_CARD => 4,
        SPATIAL_HUNTER_CARD => 5,
    ];

    function __construct(
        protected Game $game,
    ) {
        parent::__construct(
            PowerCard::class,
            [
                new ItemLocation('deck', autoReshuffleFrom: 'discard'),
            ],
        );

        self::$KEEP_CARDS_LIST = [
            'base' => [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48],
            'dark' => [1,2,3,4,5,6,7,8,9,10,11,12,13,  15,16,17,18,19,  21,22,23,24,25,26,  29,30,31,32,33,34,  36,37,38,  40,41,42,43,44,45,46,47,48, 49,50,51,52,53,54,55],
            'origins' => array_merge([
                DETRITIVORE_CARD,
                MEDIA_FRIENDLY_CARD,
                ACID_ATTACK_CARD,
                EVEN_BIGGER_CARD,
                WINGS_CARD,
                HERD_CULLER_CARD,
                ALIEN_ORIGIN_CARD,
                FREEZE_TIME_CARD,
                FRIEND_OF_CHILDREN_CARD,
                RAPID_HEALING_CARD,
                REGENERATION_CARD,
                POISON_QUILLS_CARD,
                ALPHA_MONSTER_CARD,
                CAMOUFLAGE_CARD,
                HERBIVORE_CARD,
                GOURMET_CARD,
                SPIKED_TAIL_CARD,
                COMPLETE_DESTRUCTION_CARD,
                GIANT_BRAIN_CARD,
                ENERGY_DRINK_CARD,
                PARASITIC_TENTACLES_CARD,
                JETS_CARD,
                NOVA_BREATH_CARD,
                ROOTING_FOR_THE_UNDERDOG_CARD,
                BACKGROUND_DWELLER_CARD,
            ], self::$ORIGINS_CARDS_EXCLUSIVE_KEEP_CARDS_LIST),
        ];

        self::$DISCARD_CARDS_LIST = [
            'base' => [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18],
            'dark' => [1,2,3,4,5,6,7,8,9,10,  12,13,  15,16,17,18,19],
            'origins' => array_merge([
                CORNER_STORE_CARD,
                TANK_CARD,
                SKYSCRAPER_CARD,
                DEATH_FROM_ABOVE_CARD,
                GAS_REFINERY_CARD,
                NATIONAL_GUARD_CARD,
                FLAME_THROWER_CARD,
                HEAL_CARD,
                EVACUATION_ORDER_1_CARD,
                HIGH_ALTITUDE_BOMBING_CARD,
                FRENZY_CARD,
            ], self::$ORIGINS_CARDS_EXCLUSIVE_DISCARD_CARDS_LIST),
        ];
    }

    function setup(bool $isOrigins, bool $isDarkEdition, int $mindbugCardsSetting) {
        $cards = [];

        if ($mindbugCardsSetting > 0) {
            $mindbugCards = array_merge(self::$MINDBUG_CARDS_KEEP_CARDS_LIST, self::$MINDBUG_CARDS_DISCARD_CARDS_LIST, self::$MINDBUG_CARDS_CONSUMABLE_CARDS_LIST);
            foreach($mindbugCards as $value) { // keep  
                $cards[] = ['location' => 'deck', 'type' => $value, 'type_arg' => 0];
            }
        }

        if ($mindbugCardsSetting !== 2) {
            $gameVersion = $isOrigins ? 'origins' : ($isDarkEdition ? 'dark' : 'base');
            
            foreach(self::$KEEP_CARDS_LIST[$gameVersion] as $value) { // keep  
                $cards[] = ['location' => 'deck', 'type' => $value, 'type_arg' => 0];
            }
            
            foreach(self::$DISCARD_CARDS_LIST[$gameVersion] as $value) { // discard
                $type = ($isOrigins ? 0 : 100) + $value;
                $cards[] = ['location' => 'deck', 'type' => $type, 'type_arg' => 0];
            }
        }

        if (($mindbugCardsSetting === 2 || (!$isOrigins && !$isDarkEdition)) && $this->game->tableOptions->get(ORIGINS_EXCLUSIVE_CARDS_OPTION) == 2) {        
            foreach(self::$ORIGINS_CARDS_EXCLUSIVE_KEEP_CARDS_LIST as $value) { // keep  
                $cards[] = ['location' => 'deck', 'type' => $value, 'type_arg' => 0];
            }            
            foreach(self::$ORIGINS_CARDS_EXCLUSIVE_DISCARD_CARDS_LIST as $value) { // discard
                $cards[] = ['location' => 'deck', 'type' => $value, 'type_arg' => 0];
            }
        }

        $this->createCards($cards);

        if ($this->game->isHalloweenExpansion()) { 
            $cards = [];

            for($value=1; $value<=12; $value++) { // costume
                $type = 200 + $value;
                $cards[] = ['location' => 'costumedeck', 'type' => $type, 'type_arg' => 0];
            }

            $this->createCards($cards);
            $this->shuffle('costumedeck'); 
        }

        if ($this->game->isMutantEvolutionVariant()) {            
            $cards = [ // transformation
                ['location' => 'mutantdeck', 'type' => 301, 'type_arg' => 0, 'item_nbr' => 6]
            ];

            $this->createCards($cards);
        }
    }

    public function getClassName(?array $dbItem): ?string {
        $cardType = intval($dbItem['card_type']);
        if (!array_key_exists($cardType, POWER_CARD_CLASSES)) {
            return null;
            //throw new \BgaSystemException('Unexisting EvolutionCard class');
        }

        $className = PowerCard::class;
        $namespace = substr($className, 0, strrpos($className, '\\'));
        return $namespace . '\\' . POWER_CARD_CLASSES[$cardType];
    }

    function getCardBaseCost(int $cardType): int {
        $cost = self::$CARD_COST[$cardType];

        // new costs for the Dark Edition
        if (($cardType ===  16 || $cardType ===  19) && $this->game->isDarkEdition()) {
            return 6;
        }
        if ($cardType ===  22 && $this->game->isDarkEdition()) {
            return 5;
        }
        if ($cardType ===  42 && $this->game->isDarkEdition()) {
            return 3;
        }

        return $cost;
    }

    /**
     * Return cards, ordered by location_arg for legacy purposes.
     * 
     * @return PowerCard[]
     */
    public function getCardsInLocationOldOrder(string $location, ?int $locationArg = null) {
        $cards = $this->getCardsInLocation($location, $locationArg);
        usort($cards, fn($a, $b) => $a->location_arg <=> $b->location_arg);
        return $cards;
    }

    public function getDeckCount(): int {
        return $this->countCardsInLocation('deck');
    }

    public function getTopDeckCard(bool $onlyPublic = true): ?PowerCard {
        $card = $this->getCardOnTopOldOrder('deck');
        return $onlyPublic ? PowerCard::onlyId($card) : $card;
    }

    /**
     * @return PowerCard[]
     */
    public function getCardsOnTopOldOrder(int $number, string $location): array {
        $cards = $this->getCardsInLocationOldOrder($location);
        return count($cards) > 0 ? array_slice($cards, -$number) : [];
    }

    public function getCardOnTopOldOrder(string $location): ?PowerCard {
        $cards = $this->getCardsOnTopOldOrder(1, $location);
        return count($cards) > 0 ? $cards[0] : null;
    }

    /**
     * @return PowerCard[]
     */
    public function getCardsOfType(int $type): array {
        return $this->getCardsByFieldName('type', [$type]);
    }

    /**
     * @return PowerCard[]
     */
    public function getPlayerCardsOfType(int $type, int $playerId): array {
        return Arrays::filter($this->getCardsByFieldName('type', [$type]), fn($card) => $card->location === 'hand' && $card->location_arg === $playerId);
    }

    /**
     * @return PowerCard
     */
    public function pickCardForLocationOldOrder(string $fromLocation, string $toLocation, int $toLocationArg = 0) {
        $item = $this->getCardOnTopOldOrder($fromLocation);
        if ($item === null && $fromLocation === 'deck') {
            $this->moveAllCardsInLocation('discard', 'deck');
            $this->shuffle('deck');
            $item = $this->getCardOnTopOldOrder($fromLocation);
        }

        if ($item !== null) {
            $this->moveCard($item, $toLocation, $toLocationArg);
        }
        return $item;
    }

    /**
     * @return PowerCard[]
     */
    public function getTable(): array {
        return $this->getCardsInLocationOldOrder('table');
    }

    /**
     * Returns all real power cards the player have.
     * Includes disabled Keep cards.
     * 
     * @return PowerCard[]
     */
    public function getPlayerReal(int $playerId): array {
        return $this->getCardsInLocation('hand', $playerId);
    }

    /**
     * Returns all virtual power cards the player have (an evolution copied with Mimick or Fluxling will be returned as a power card).
     * A virtual card will have a negative id.
     * Excludes disabled Keep cards.
     * 
     * @return PowerCard[]
     */
    function getPlayerVirtual(int $playerId, bool $virtualFirst = false): array {
        $cards = $this->getPlayerReal($playerId);
        if (!$this->game->keepAndEvolutionCardsHaveEffect()) {
            $cards = Arrays::filter($cards, fn($card) => $card->type >= 100);
        }

        $mimicCard = Arrays::find($cards, fn($card) => $card->type === MIMIC_CARD);
        if ($mimicCard) {
            $mimickedCardId = $this->game->getMimickedCardId(MIMIC_CARD);
            if ($mimickedCardId) {
                $virtualCard = $this->getCardById($mimickedCardId);
                if ($virtualCard) {
                    $virtualCard->id = -$virtualCard->id;
                    $virtualCard->mimickingCardId = $mimicCard->id;
                    if ($virtualFirst) {
                        array_unshift($cards, $virtualCard);
                    } else {
                        $cards[] = $virtualCard;
                    }
                }
            }
        }

        $fluxlingTile = Arrays::find($this->game->wickednessTiles->getPlayerTiles($playerId), fn($tile) => $tile->type === FLUXLING_WICKEDNESS_TILE);
        if ($fluxlingTile) {
            $mimickedCardId = $this->game->getMimickedCardId(FLUXLING_WICKEDNESS_TILE);
            if ($mimickedCardId) {
                $virtualCard = $this->getCardById($mimickedCardId);
                if ($virtualCard) {
                    $virtualCard->id = -$virtualCard->id;
                    $virtualCard->mimickingTileId = $fluxlingTile->id;
                    if ($virtualFirst) {
                        array_unshift($cards, $virtualCard);
                    } else {
                        $cards[] = $virtualCard;
                    }
                }
            }
        }

        return $cards;
    }

    /**
     * @return PowerCard[]
     */
    public function getReserved(int $playerId, ?int $locationArg = null): array {
        return $this->getCardsInLocation('reserved'.$playerId, $locationArg);
    }

    /**
     * @return Damage[]|null
     */
    function applyEffects(PowerCard $card, int $playerId, int $stateAfter) { // return ?$damages
        $cardType = $card->type;
        if ($cardType < 100 && !$this->game->keepAndEvolutionCardsHaveEffect()) {
            return;
        }

        return $this->immediateEffect($card, new Context($this->game, currentPlayerId: $playerId, stateAfter: $stateAfter));
    }

    public function immediateEffect(PowerCard $card, Context $context) {
        if (method_exists($card, 'immediateEffect')) {
            /** @disregard */
            return $card->immediateEffect($context);
        }
    }

    public function onIncDieRollCount(Context $context): int {
        $cards = $this->getPlayerVirtual($context->currentPlayerId);
        $inc = 0;

        foreach ($cards as $card) {
            if (method_exists($card, 'incDieRollCount')) {
                /** @disregard */
                $inc += $card->incDieRollCount($context);
            }
        }

        return $inc;
    }

    public function onIncDieCount(Context $context): int {
        $cards = $this->getPlayerVirtual($context->currentPlayerId);
        $inc = 0;

        foreach ($cards as $card) {
            if (method_exists($card, 'incDieCount')) {
                /** @disregard */
                $inc += $card->incDieCount($context);
            }
        }

        return $inc;
    }

    public function onAddSmashes(Context $context): array {
        $cards = $this->getPlayerVirtual($context->currentPlayerId);
        $cards = Arrays::filter($cards, fn($card) => method_exists($card, 'addSmashes'));
        $addedByCards = 0;
        $addingCards = [];

        // to make sure antimatter beam multiplication is done after barbs addition
        /** @var AddSmashesPowerCard[] $cards */
        usort($cards, 
            // Sort by the return value of addSmashesOrder, smaller order first
            fn($a, $b) => (method_exists($a, 'addSmashesOrder') ? $a->addSmashesOrder() : 1) <=> (method_exists($b, 'addSmashesOrder') ? $b->addSmashesOrder() : 1)
        );

        foreach ($cards as $card) {
            /** @disregard */
            $addedByCard = $card->addSmashes($context);
            $addedByCards += $addedByCard;
            $context->addedSmashes += $addedByCard;
            if ($addedByCard > 0) {
                $addingCards[] = $card->type;
            }
        }

        return [$addedByCards, $addingCards];
    }

    public function onPlayerEliminated(Context $context) {
        $damages = [];
        $cards = $this->getPlayerVirtual($context->currentPlayerId);

        foreach ($cards as $card) {
            if (method_exists($card, 'onPlayerEliminated')) {
                /** @disregard */
                $newDamages = $card->onPlayerEliminated($context);
                if ($newDamages) {
                    $damages = array_merge($damages, $newDamages);
                }
            }
        }
        return $damages;
    }

    public function getUnmetConditionRequirement(PowerCard $card, Context $context): ?NotificationMessage {
        if (method_exists($card, 'getUnmetConditionRequirement')) {
            /** @disregard */
            return $card->getUnmetConditionRequirement($context);
        }
        return null;
    }

    public function activateKeyword(PowerCard &$card, string $keyword): void {
        $card->activated = new ActivatedConsumableKeyword($keyword);
        $this->updateCard($card, ['activated']);
    }

    public function setActivatedKeywordTarget(PowerCard $card, int $targetPlayerId): void {
        $card->activated->targetPlayerId = $targetPlayerId;
        $this->updateCard($card, ['activated']);
    }

    /**
     * @return int 0 for table, else player id
     */
    function getCardFrom(PowerCard $card): int {
        if ($card->location === 'table') {
            return 0;
        } else if ($card->location === 'discard') {
            return -1;
        } else if ($card->location === 'hand') {
            return $card->location_arg;
        }
        
        throw new UserException('Invalid location to take the card');
    }
}
