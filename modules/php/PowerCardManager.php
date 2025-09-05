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
use Bga\Games\KingOfTokyo\Objects\Context;
use Bga\Games\KingOfTokyo\PowerCards\PowerCard;

const POWER_CARD_CLASSES = [
    JET_FIGHTERS_CARD => 'JetFighters',
];

class PowerCardManager extends ItemManager {
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

    ];

    function __construct(
        protected $game,
    ) {
        parent::__construct(
            PowerCard::class,
            [
                new ItemLocation('deck', true, autoReshuffleFrom: 'discard'),
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

    function setup(bool $isOrigins, bool $isDarkEdition) {
        $cards = [];

        $gameVersion = $isOrigins ? 'origins' : ($isDarkEdition ? 'dark' : 'base');
        
        foreach(self::$KEEP_CARDS_LIST[$gameVersion] as $value) { // keep  
            $cards[] = ['location' => 'deck', 'type' => $value, 'type_arg' => 0, 'nbr' => 1];
        }
        
        foreach(self::$DISCARD_CARDS_LIST[$gameVersion] as $value) { // discard
            $type = ($isOrigins ? 0 : 100) + $value;
            $cards[] = ['location' => 'deck', 'type' => $type, 'type_arg' => 0, 'nbr' => 1];
        }

        if (!$isOrigins && !$isDarkEdition && $this->game->tableOptions->get(ORIGINS_EXCLUSIVE_CARDS_OPTION) == 2) {        
            foreach(self::$ORIGINS_CARDS_EXCLUSIVE_KEEP_CARDS_LIST as $value) { // keep  
                $cards[] = ['location' => 'deck', 'type' => $value, 'type_arg' => 0, 'nbr' => 1];
            }            
            foreach(self::$ORIGINS_CARDS_EXCLUSIVE_DISCARD_CARDS_LIST as $value) { // discard
                $cards[] = ['location' => 'deck', 'type' => $value, 'type_arg' => 0, 'nbr' => 1];
            }
        }

        $this->createItems($cards);

        if ($this->game->isHalloweenExpansion()) { 
            $cards = [];

            for($value=1; $value<=12; $value++) { // costume
                $type = 200 + $value;
                $cards[] = ['location' => 'costumedeck', 'type' => $type, 'type_arg' => 0, 'nbr' => 1];
            }

            $this->createItems($cards);
            $this->shuffle('costumedeck'); 
        }

        if ($this->game->isMutantEvolutionVariant()) {            
            $cards = [ // transformation
                ['location' => 'mutantdeck', 'type' => 301, 'type_arg' => 0, 'nbr' => 6]
            ];

            $this->createItems($cards);
        }
    }

    protected function getClassName(?array $dbItem): ?string {
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
     */
    public function getCardsInLocation(string $location, ?int $locationArg = null) {
        $cards = $this->getItemsInLocation($location, $locationArg);
        usort($cards, fn($a, $b) => $a->location_arg <=> $b->location_arg);
        return $cards;
    }

    public function getDeckCount(): int {
        return $this->countItemsInLocation('deck');
    }

    public function getTopDeckCard(bool $onlyPublic = true): ?PowerCard {
        $card = $this->getCardOnTop('deck');
        return $onlyPublic ? PowerCard::onlyId($card) : $card;
    }

    public function getCardsOnTop(int $number, string $location): array {
        $cards = $this->getCardsInLocation($location);
        return count($cards) > 0 ? array_slice($cards, -$number) : [];
    }

    public function getCardOnTop(string $location): PowerCard {
        $cards = $this->getCardsOnTop(1, $location);
        return count($cards) > 0 ? $cards[0] : null;
    }

    public function getCardsOfType(int $type): array {
        return $this->getItemsByFieldName('type', [$type]);
    }

    public function getPlayerCardsOfType(int $type, int $playerId): array {
        return Arrays::filter($this->getItemsByFieldName('type', [$type]), fn($card) => $card->location === 'hand' && $card->location_arg === $playerId);
    }

    public function pickCardForLocation(string $fromLocation, string $toLocation, int $toLocationArg = 0) {
        $item = $this->getCardOnTop($fromLocation);
        if ($item === null && $fromLocation === 'deck') {
            $this->moveAllItemsInLocation('discard', 'deck');
            $this->shuffle('deck');
            $item = $this->getCardOnTop($fromLocation);
        }

        if ($item !== null) {
            $this->moveItem($item, $toLocation, $toLocationArg);
        }
        return $item;
    }

    public function getTable(): array {
        return $this->getCardsInLocation('table');
    }

    public function getPlayer(int $playerId): array {
        return $this->getItemsInLocation('hand', $playerId);
    }

    public function getReserved(int $playerId, ?int $locationArg = null): array {
        return $this->getItemsInLocation('reserved'.$playerId, $locationArg);
    }

    public function immediateEffect(PowerCard $card, Context $context) {
        if (method_exists($card, 'immediateEffect')) {
            /** @disregard */
            return $card->immediateEffect($context);
        }
    }

}
