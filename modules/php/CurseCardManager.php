<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo;

require_once(__DIR__.'/framework-prototype/item/item.php');
require_once(__DIR__.'/framework-prototype/item/item-field.php');
require_once(__DIR__.'/framework-prototype/item/item-location.php');
require_once(__DIR__.'/framework-prototype/item/item-manager.php');

use Bga\GameFrameworkPrototype\Item\ItemLocation;
use \Bga\GameFrameworkPrototype\Item\ItemManager;
use Bga\Games\KingOfTokyo\Objects\Context;
use Bga\Games\KingOfTokyo\CurseCards\CurseCard;

const CURSE_CARD_CLASSES = [
    PHARAONIC_EGO_CURSE_CARD => 'PharaonicEgo',
    // TODO migrate applyAnkhEffect / applySnakeEffect onto other card classes
    BOW_BEFORE_RA_CURSE_CARD => 'BowBeforeRa',
];

class CurseCardManager extends ItemManager {

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
        $this->createItems($cards);
        $this->shuffle('deck'); 

        // init first curse card
        $this->pickItemForLocation('deck', null, 'table');
    }

    protected function getClassName(?array $dbItem): ?string {
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
        return $this->getItemsInLocation('table')[0];
    }

    function getTopDeck() {
        return CurseCard::onlyId($this->getItemOnTop('deck'));
    }
    
    function changeCurseCard(int $playerId) {
        $countRapidHealingBefore = 0;
        if ($playerId > 0) {
            $countRapidHealingBefore = $this->game->countCardOfType($playerId, RAPID_HEALING_CARD);
        }        

        $this->game->removeCursePermanentEffectOnReplace();

        $this->moveAllItemsInLocation('table', 'discard');

        $card = $this->pickItemForLocation('deck', null, 'table');

        $this->game->notify->all('changeCurseCard', clienttranslate('Die of fate is on [dieFateEye], Curse card is changed'), [
            'card' => $card,
            'hiddenCurseCardCount' => $this->countItemsInLocation('deck'),
            'topCurseDeckCard' => $this->getTopDeck(),
        ]);

        $this->immediateEffect($card, new Context($this->game));
        
        if ($playerId > 0) {
            $this->game->toggleRapidHealing($playerId, $countRapidHealingBefore);
        }
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
