<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo;

require_once(__DIR__.'/framework-prototype/item/item.php');
require_once(__DIR__.'/framework-prototype/item/item-field.php');
require_once(__DIR__.'/framework-prototype/item/item-location.php');
require_once(__DIR__.'/framework-prototype/item/item-manager.php');
require_once(__DIR__.'/framework-prototype/item/card-manager.php');

use Bga\GameFrameworkPrototype\Item\ItemLocation;
use \Bga\GameFrameworkPrototype\Item\CardManager;
use Bga\Games\KingOfTokyo\Objects\Context;
use Bga\Games\KingOfTokyo\CurseCards\CurseCard;

const CURSE_CARD_CLASSES = [
    PHARAONIC_EGO_CURSE_CARD => 'PharaonicEgo',
    ISIS_S_DISGRACE_CURSE_CARD => 'IsisDisgrace',
    THOT_S_BLINDNESS_CURSE_CARD => 'ThotBlindness',
    TUTANKHAMUN_S_CURSE_CURSE_CARD =>  'TutankhamunCurse',
    BURIED_IN_SAND_CURSE_CARD => 'BuriedInSand',
    RAGING_FLOOD_CURSE_CARD => 'RagingFlood',
    HOTEP_S_PEACE_CURSE_CARD => 'HotepPeace',
    SET_S_STORM_CURSE_CARD => 'SetStorm',
    BUILDERS_UPRISING_CURSE_CARD => 'BuildersUprising',
    INADEQUATE_OFFERING_CURSE_CARD => 'InadequateOffering',
    BOW_BEFORE_RA_CURSE_CARD => 'BowBeforeRa',
    VENGEANCE_OF_HORUS_CURSE_CARD => 'VengeanceOfHorus',
    ORDEAL_OF_THE_MIGHTY_CURSE_CARD => 'OrdealOfTheMighty',
    ORDEAL_OF_THE_WEALTHY_CURSE_CARD => 'OrdealOfTheWealthy',
    ORDEAL_OF_THE_SPIRITUAL_CURSE_CARD => 'OrdealOfTheSpiritual',
    RESURRECTION_OF_OSIRIS_CURSE_CARD => 'ResurrectionOfOsiris',
    FORBIDDEN_LIBRARY_CURSE_CARD => 'ForbiddenLibrary',
    CONFUSED_SENSES_CURSE_CARD => 'ConfusedSenses',
    PHARAONIC_SKIN_CURSE_CARD => 'PharaonicSkin',
    KHEPRI_S_REBELLION_CURSE_CARD => 'KhepriRebellion',
    BODY_SPIRIT_AND_KA_CURSE_CARD => 'BodySpiritAndKa',
    FALSE_BLESSING_CURSE_CARD => 'FalseBlessing',
    GAZE_OF_THE_SPHINX_CURSE_CARD => 'GazeOfTheSphinx',
    SCRIBE_S_PERSEVERANCE_CURSE_CARD => 'ScribePerseverance',
];

class CurseCardManager extends CardManager {

    function __construct(
        protected $game,
    ) {
        parent::__construct(
            CurseCard::class,
            [
                new ItemLocation('deck', autoReshuffleFrom: 'discard'),
            ],
        );
    }

    function setup() {
        for($value=1; $value<=24; $value++) {
            $cards[] = ['type' => $value, 'location' => 'deck', 'type_arg' => 0, 'nbr' => 1];
        }
        $this->createCards($cards);
        $this->shuffle('deck'); 

        // init first curse card
        $this->pickCardForLocation('deck', null, 'table');
    }

    public function getClassName(?array $dbItem): ?string {
        $cardType = intval($dbItem['card_type']);
        if (!array_key_exists($cardType, CURSE_CARD_CLASSES)) {
            return null;
            //throw new \BgaSystemException('Unexisting CurseCard class');
        }

        $className = CurseCard::class;
        $namespace = substr($className, 0, strrpos($className, '\\'));
        return $namespace . '\\' . CURSE_CARD_CLASSES[$cardType];
    }

    function getCurrent() {
        return $this->getCardsInLocation('table')[0];
    }

    function getTopDeck() {
        return CurseCard::onlyId($this->getCardOnTop('deck'));
    }

    public function immediateEffect(CurseCard $card, Context $context) {
        if (method_exists($card, 'immediateEffect')) {
            /** @disregard */
            return $card->immediateEffect($context);
        }
    }

    public function applyAnkhEffect(CurseCard $card, Context $context) {
        if (method_exists($card, 'applyAnkhEffect')) {
            /** @disregard */
            return $card->applyAnkhEffect($context);
        }
    }

    public function applySnakeEffect(CurseCard $card, Context $context) {
        if (method_exists($card, 'applySnakeEffect')) {
            /** @disregard */
            return $card->applySnakeEffect($context);
        }
    }
}
