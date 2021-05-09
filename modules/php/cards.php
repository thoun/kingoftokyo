<?php

namespace KOT\States;

require_once(__DIR__.'/objects/card.php');

use KOT\Objects\Card;

trait CardsTrait {

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////

    

    function getCardName(int $cardTypeId) {
        switch($cardTypeId) {
            // KEEP
            case 1: return _("Acid Attack");
            case 2: return _("Alien Origin");
            case 3: return _("Alpha Monster");
            case 4: return _("Armor Plating");
            case 5: return _("Background Dweller");
            case 6: return _("Burrowing");
            case 7: return _("Camouflage");
            case 8: return _("Complete Destruction");
            case 9: return _("Media Friendly");
            case 10: return _("Eater of the Dead");
            case 11: return _("Energy Hoarder");
            case 12: return _("Even Bigger");
            case 13: case 14: return _("Extra Head");
            case 15: return _("Fire Breathing");
            case 16: return _("Freeze Time");
            case 17: return _("Friend of Children");
            case 18: return _("Giant Brain");
            case 19: return _("Gourmet");
            case 20: return _("Healing Ray");
            case 21: return _("Herbivore");
            case 22: return _("Herd Culler");
            case 23: return _("It Has a Child");
            case 24: return _("Jets");
            case 25: return _("Made in a Lab");
            case 26: return _("Metamorph");
            case 27: return _("Mimic");
            case 28: return _("Battery Monster");
            case 29: return _("Nova Breath");
            case 30: return _("Detritivore");
            case 31: return _("Opportunist");
            case 32: return _("Parasitic Tentacles");
            case 33: return _("Plot Twist");
            case 34: return _("Poison Quills");
            case 35: return _("Poison Spit");
            case 36: return _("Psychic Probe");
            case 37: return _("Rapid Healing");
            case 38: return _("Regeneration");
            case 39: return _("Rooting for the Underdog");
            case 40: return _("Shrink Ray");
            case 41: return _("Smoke Cloud");
            case 42: return _("Solar Powered");
            case 43: return _("Spiked Tail");
            case 44: return _("Stretchy");
            case 45: return _("Energy Drink");
            case 46: return _("Urbavore");
            case 47: return _("We're Only Making It Stronger");
            case 48: return _("Wings");
            //case 49: return _("Cannibalistic");
            //case 50: return _("Intimidating Roar");
            //case 51: return _("Monster Sidekick");
            //case 52: return _("Reflective Hide");
            //case 53: return _("Sleep Walker");
            //case 54: return _("Super Jump");
            //case 55: return _("Throw a Tanker");
            //case 56: return _("Thunder Stomp");
            //case 57: return _("Unstable DNA");
            // DISCARD
            case 101: return _("Apartment Building");
            case 102: return _("Commuter Train");
            case 103: return _("Corner Store");
            case 104: return _("Death From Above");
            case 105: return _("Energize");
            case 106: case 107: return _("Evacuation Orders");
            case 108: return _("Flame Thrower");
            case 109: return _("Frenzy");
            case 110: return _("Gas Refinery");
            case 111: return _("Heal");
            case 112: return _("High Altitude Bombing");
            case 113: return _("Jet Fighters");
            case 114: return _("National Guard");
            case 115: return _("Nuclear Power Plant");
            case 116: return _("Skyscraper");
            case 117: return _("Tank");
            case 118: return _("Vast Storm");
            //case 119: return _("Amusement Park");
            //case 120: return _("Army");
        }
        return null;
    }

    function initCards() {
        $cards = [];
        
        for($value=1; $value<=48; $value++) { // keep
            if (!in_array($value, $this->KEEP_CARDS_TODO)) { // TODO remove filter       
                $cards[] = ['type' => $value, 'type_arg' => 0, 'nbr' => 1];
            }
        }
        
        for($value=101; $value<=118; $value++) { // discard
            //$cards[] = ['type' => $value, 'type_arg' => 0, 'nbr' => 1];
        }

        $this->cards->createCards($cards, 'deck');
        $this->cards->shuffle('deck'); 
    }

    function getCardFromDb(array $dbCard) {
        if (!$dbCard || !array_key_exists('id', $dbCard)) {
            throw new Error('card doesn\'t exists '.json_encode($dbCard));
        }
        if (!$dbCard || !array_key_exists('location', $dbCard)) {
            throw new Error('location doesn\'t exists '.json_encode($dbCard));
        }
        return new Card($dbCard);
    }

    function getCardsFromDb(array $dbCards) {
        return array_map(function($dbCard) { return $this->getCardFromDb($dbCard); }, array_values($dbCards));
    }

    function applyEffects($card, $playerId) {
        $type = $card->type;

        switch($type) {
            // KEEP
            case 5: return _("<strong>You can always reroll any [dice3]</strong> you have.");
            case 6: return _("<strong>Add [diceSmash] to your Roll while you are in Tokyo. When you Yield Tokyo, the monster taking it loses 1[heart].</strong>");
            case 7: return _("If you lose [heart], roll a die for each [heart] you lost. <strong>Each [diceHeart] reduces the loss by 1[heart].</strong>");
            case 8: return _("If you roll [dice1][dice2][dice3][diceHeart][diceSmash][diceEnergy] <strong>gain 9[Star]</strong> in addition to the regular effects.");
            case 9: return _("<strong>Gain 1[Star]</strong> whenever you buy a Power card.");
            case 10: return _("<strong>Gain 3[Star]</strong> every time a Monster's [Heart] goes to 0.");
            case 12: 
                $this->applyGetHealth($playerId, 2, $type); 
                break;
            case 15: return _("<strong>Your neighbors lose 1[heart]</strong> when you roll at least one [diceSmash].");
            case 16: return _("On a turn where you score [dice1][dice1][dice1], <strong>you can take another turn</strong> with one less die.");
            case 18: return _("<strong>You have one extra die Roll</strong> each turn.");
            case 20: return _("<strong>You can use your [diceHeart] to make other Monsters gain [Heart].</strong> Each Monster must pay you 2[Energy] (or 1[Energy] if it's their last one) for each [Heart] they gain this way");
            case 22: return _("You can <strong>change one of your dice to a [dice1]</strong> each turn.");
            case 24: return _("<strong>You don't lose [Heart]<strong> if you decide to Yield Tokyo.");
            case 25: return _("During the Buy Power cards step, you can <strong>peek at the top card of the deck and buy it</strong> or put it back on top of the deck.");
            case 26: return _("At the end of your turn you can <strong>discard any [keep] cards you have to gain their full cost in [Energy].</strong>");
            case 27: return _("<strong>Choose a [keep] card any monster has in play</strong> and put a Mimic token on it. <strong>This card counts as a duplicate of that card as if you had just bought it.</strong> Spend 1[Energy] at the start of your turn to move the Mimic token and change the card you are mimicking.");
            case 28: 
                self::setGameStateValue('energyOnBatteryMonster', 6);
                break;
            case 29: return _("<strong>Your [diceSmash] damage all other Monsters.</strong>");
            case 31: return _("<strong>Whenever a Power card is revealed you have the option of buying it</strong> immediately.");
            case 32: return _("<strong>You can buy Power cards from other monsters.</strong> Pay them the [Energy] cost.");
            case 33: return _("Before resolving your dice, you may <strong>change one die to any result</strong>. Discard when used.");
            case 35: return _("Give one <i>Poison</i> token to each Monster you Smash with your [diceSmash]. <strong>At the end of their turn, Monsters lose 1[Heart] for each <i>Poison</i> token they have on them.</strong> A <i>Poison</i> token can be discarded by using a [diceHeart] instead of gaining 1[Heart].");
            case 36: return _("You can reroll a die of your choice after the last Roll of each other Monster. If the reroll [diceHeart], discard this card.");
            case 37: return _("Spend 2[Energy] at any time to <strong>gain 1[Heart].</strong>");
            case 40: return _("Give 1 <i>Shrink Ray</i> to each Monster you Smash with your [diceSmash]. <strong>At the beginning of their turn, Monster roll 1 less dice for each <i>Shrink Ray</i> token they have on them</strong>. A <i>Shrink Ray</i> token can be discarded by using a [diceHeart] instead of gaining 1[Heart].");
            case 41: return _("Place 3 <i>Smoke</i> counters on this card. <strong>Spend 1 <i>Smoke</i> counter for an extra Roll.</strong> Discard this card when all <i>Smoke</i> counters are spent.");
            case 44: return _("Before resolving your dice, you can spend 2[Energy] to <strong>change one of your dice to any result.</strong>");
            case 45: return _("Spend 1[Energy] to <strong>get 1 extra die Roll.</strong>");
            case 48: return _("<strong>Spend 2[Energy] to lose [Heart]<strong> this turn.");
            
            // DISCARD
            case 101: 
                $this->applyGetPoints($playerId, 3, $type);
                break;
            case 102:
                $this->applyGetPoints($playerId, 2, $type);
                break;
            case 103:
                $this->applyGetPoints($playerId, 1, $type);
                break;
            case 104: 
                $this->applyGetPoints($playerId, 2, $type);
                if (!$this->inTokyo($playerId)) {
                    // take control of Tokyo
                    if ($this->isTokyoEmpty(false)) {
                        $this->moveToTokyo($playerId, false);
                    } else if ($this->tokyoBayUsed() && $this->isTokyoEmpty(true)) {
                        $this->moveToTokyo($playerId, true);
                    } else {
                        // we force Tokyo city player out
                        // TOCHECK If 5-6 players and both spots used, we enter tokyo city ? Considered Yes
                        $this->leaveTokyo($this->getPlayerIdInTokyoCity());
                        $this->moveToTokyo($playerId, false);
                    }
                }
                break;
            case 105:
                $this->applyGetEnergy($playerId, 9, $type);
                break;
            case 106: case 107:
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $this->applyLosePoints($otherPlayerId, 5, $type);
                }
                break;
            case 108: 
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $this->applyDamage($otherPlayerId, 2, $playerId, $type);
                }
                break;
            case 109: 
                $this->setGameStateValue('playAgainAfterTurn', 1);
                break;
            case 110: 
                $this->applyGetPoints($playerId, 2, $type);
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $this->applyDamage($otherPlayerId, 3, $playerId, $type);
                }
                break;
            case 111:
                $this->applyGetHealth($playerId, 2, $type);
                break;
            case 112: 
                $playersIds = $this->getPlayersIds();
                foreach ($playersIds as $pId) {
                    $this->applyDamage($pId, 3, $playerId, $type);
                }
                break;
            case 113: 
                $this->applyGetPoints($playerId, 5, $type);
                $this->applyDamage($playerId, 4, $playerId, $type);
                break;
            case 114:
                $this->applyGetPoints($playerId, 2, $type);
                $this->applyDamage($playerId, 2, $playerId, $type);
                break;
            case 115:
                $this->applyGetPoints($playerId, 2, $type);
                $this->applyGetHealth($playerId, 3, $type);
                break;
            case 116:
                $this->applyGetPoints($playerId, 4, $type);
                break;
            case 117:
                $this->applyGetPoints($playerId, 4, $type);
                $this->applyDamage($playerId, 3, $playerId, $type);
                break;
            case 118: 
                $this->applyGetPoints($playerId, 2, $type);
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $energy = $this->getPlayerEnergy($otherPlayerId);
                    $lostEnergy = floor($energy / 2);
                    $this->applyLoseEnergy($otherPlayerId, $lostEnergy, $type);
                }
                break;
            case 119:
                $count = $this->cards->countCardInLocation('hand', $player_id);
                $this->applyGetPoints($playerId, $count, $type);
                $this->applyDamage($playerId, $count, $playerId, $type);
                
                break;
            case 120:
        }
    }

    function countCardOfType($playerId, $cardType, $includeMimick = true) {
        return count($this->cards->getCardsOfTypeInLocation($cardType, null, 'hand', $playerId));
        // TODO mimick
    }

    function countExtraHead($playerId) {
        return $this->countCardOfType($playerId, 13) + $this->countCardOfType($playerId, 14);
    }

    function getCardCost($playerId, $cardType) {
        $cardCost = $this->CARD_COST[$cardType];

        // alien origin
        $countAlienOrigin = $this->countCardOfType($playerId, 2);

        return max($cardCost - $countAlienOrigin, 0);
    }

    function canBuyCard($playerId, $cost) {
        return $cost <= $this->getPlayerEnergy($playerId);
    }

    function applyItHasAChild($playerId) {
        $playerName = $this->getPlayerName($playerId);
        // discard all cards
        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $playerId));
        $this->cards->moveAllCardsInLocation('hand', 'discard', $playerId);        
        self::notifyAllPlayers("removeCards", '', [
            'playerId' => $playerId,
            'player_name' => $playerName,
            'cards' => $cards,
        ]);

        // lose all stars
        $points = 0;
        self::DbQuery("UPDATE player SET `player_score` = $points where `player_id` = $playerId");
        self::notifyAllPlayers('points','', [
            'playerId' => $playerId,
            'player_name' => $playerName,
            'points' => $points,
        ]);

        // get back to 10 heart
        $health = 10;
        self::DbQuery("UPDATE player SET `player_health` = $health where `player_id` = $playerId");
        self::notifyAllPlayers('health', '', [
            'playerId' => $playerId,
            'player_name' => $playerName,
            'health' => $health,
        ]);

        self::notifyAllPlayers('applyItHasAChild', clienttranslate('${player_name} reached 0 [Heart]. With ${card_name}, all cards and [Star] are lost but player gets back 10 [Heart]'), [
            'playerId' => $playerId,
            'player_name' => $playerName,
            'health' => $health,
            'card_name' => $this->getCardName(23),
        ]);

        if ($this->inTokyo($playerId)) {
            $this->leaveTokyo($playerId);
        }
    }

    function applyBatteryMonster(int $playerId) {
        $energyOnBatteryMonster = intval(self::getGameStateValue('energyOnBatteryMonster')) - 2;
        self::setGameStateValue('energyOnBatteryMonster', $energyOnBatteryMonster);

        $this->applyGetEnergyIgnoreCards($playerId, 2, 28);

        if ($energyOnBatteryMonster <= 0) {
            $card = $this->getCardFromDb(array_values($this->cards->getCardsOfTypeInLocation(28, null, 'hand', $playerId))[0]);
            $this->cards->moveCard($card->id, 'discard');        
            self::notifyAllPlayers("removeCards", '', [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'cards' => [$card],
            ]);
        }
    }

    function buyEnergyDrink() {
        $playerId = self::getActivePlayerId();

        if ($this->getPlayerEnergy($playerId) < 1) {
            throw new \Error('Not enough energy');
        }

        $this->applyLoseEnergyIgnoreCards($playerId, 1, 0);
        
        $energyDrinks = intval(self::getGameStateValue('energyDrinks')) + 1;
        self::setGameStateValue('energyDrinks', $energyDrinks);

        $this->gamestate->nextState('buyEnergyDrink');        
    }

    function removeCard(int $playerId, $card, bool $silent = false) {
        $this->cards->moveCard($card->id, 'discard');

        if (!$silent) {
            self::notifyAllPlayers("removeCards", '', [
                'playerId' => $playerId,
                'player_name' => $playerName,
                'cards' => [$card],
            ]);
        }        
    }

    function removeCardByType(int $playerId, int $cardType, bool $silent = false) {
        $card = $this->getCardFromDb(array_values($this->cards->getCardsOfTypeInLocation($cardType, null, 'hand', $playerId))[0]);

        $this->removeCard($playerId, $card, $silent);
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */

    function buyCard(int $id, int $from) {
        $playerId = self::getActivePlayerId();

        $card = $this->getCardFromDb($this->cards->getCard($id));

        // TOCHECK can it buy with parasitic tentacles whith reduced price ? Considered Yes
        $cost = $this->getCardCost($playerId, $card->type);
        if (!$this->canBuyCard($playerId, $cost)) {
            throw new \Error('Not enough energy');
        }

        self::DbQuery("UPDATE player SET `player_energy` = `player_energy` - $cost where `player_id` = $playerId");

        // media friendly
        $countMediaFriendly = $this->countCardOfType($playerId, 9);
        if ($countMediaFriendly > 0) {
            $this->applyGetPoints($playerId, $countMediaFriendly, 9);
        }

        if ($from > 0) {
            $this->removeCard($from, $card, true);
        }
        $this->cards->moveCard($id, 'hand', $playerId);

        if ($from > 0) {    
            self::notifyAllPlayers("buyCard", clienttranslate('${player_name} buy ${card_name} from ${player_name2}'), [
                'playerId' => $playerId,
                'player_name' => self::getActivePlayerName(),
                'card' => $card,
                'card_name' => $this->getCardName($card->type),
                'newCard' => null,
                'energy' => $this->getPlayerEnergy($playerId),
                'from' => $from,
                'player_name2' => $this->getPlayerName($from),
            ]);
        } else {
            $newCard = $this->getCardFromDb($this->cards->pickCardForLocation('deck', 'table'));
    
            self::notifyAllPlayers("buyCard", clienttranslate('${player_name} buy ${card_name}'), [
                'playerId' => $playerId,
                'player_name' => self::getActivePlayerName(),
                'card' => $card,
                'card_name' => $this->getCardName($card->type),
                'newCard' => $newCard,
                'energy' => $this->getPlayerEnergy($playerId),
            ]);
        }

        $this->applyEffects($card, $playerId);

        // cards effects may eliminate players
        $endGame = $this->eliminatePlayers($playerId);

        if ($endGame) {
            $this->gamestate->nextState('endGame');
        } else {
            $this->gamestate->nextState('buyCard');
        }
    }

    function renewCards() {
        $playerId = self::getActivePlayerId();

        if ($this->getPlayerEnergy($playerId) < 2) {
            throw new \Error('Not enough energy');
        }

        $cost = 2;
        self::DbQuery("UPDATE player SET `player_energy` = `player_energy` - $cost where `player_id` = $playerId");

        $this->cards->moveAllCardsInLocation('table', 'discard');
        $cards = $this->getCardsFromDb($this->cards->pickCardsForLocation(3, 'deck', 'table'));

        self::notifyAllPlayers("renewCards", clienttranslate('${player_name} renew visible cards'), [
            'playerId' => $playerId,
            'player_name' => self::getActivePlayerName(),
            'cards' => $cards,
            'energy' => $this->getPlayerEnergy($playerId),
        ]);

        $this->gamestate->nextState('renew');
    }

    function goToSellCard() {
        $this->gamestate->nextState('goToSellCard');
    }

    
    function sellCard(int $id) {
        $playerId = self::getActivePlayerId();

        $card = $this->getCardFromDb($this->cards->getCard($id));

        $fullCost = $this->CARD_COST[$card->type];

        $this->removeCard($playerId, $card, true);

        self::notifyAllPlayers("removeCards", clienttranslate('${player_name} sells ${card_name}'), [
            'playerId' => $playerId,
            'player_name' => self::getActivePlayerName(),
            'cards' => [$card],
            'card_name' =>$this->getCardName($card->type),
            'energy' => $this->getPlayerEnergy($playerId),
        ]);

        // TOCHECK can metamorph be chained with Friend of children ? Considered Yes
        $this->applyGetEnergy($playerId, $fullCost, 0);

        $this->gamestate->nextState('sellCard');
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */
    function argBuyCard() {
        $playerId = self::getActivePlayerId();
        $playerEnergy = $this->getPlayerEnergy($playerId);

        // parasitic tentacles
        $canBuyFromPlayers = $this->countCardOfType($playerId, 32);

        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('table'));
        
        $disabledCards = array_values(array_filter($cards, function ($card) use ($playerId) { return !$this->canBuyCard($playerId, $this->getCardCost($playerId, $card->type)); }));
        $disabledIds = array_map(function ($card) { return $card->id; }, $disabledCards);

        if ($canBuyFromPlayers) {
            $otherPlayersIds = $this->getOtherPlayersIds($playerId);
            foreach($otherPlayersIds as $otherPlayerId) {
                $cardsOfPlayer = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $otherPlayerId));
                // TOCHECK can it buy with parasitic tentacles whith reduced price ? Considered Yes
                $disabledCardsOfPlayer = array_values(array_filter($cardsOfPlayer, function ($card) use ($playerId) { return !$this->canBuyCard($playerId, $this->getCardCost($playerId, $card->type)); }));
                $disabledIdsOfPlayer = array_map(function ($card) { return $card->id; }, $disabledCards);
                
                $disabledIds = array_merge($disabledIds, $disabledIdsOfPlayer);
            }
        }

    
        // return values:
        return [
            'disabledIds' => $disabledIds,
            'canBuyFromPlayers' => $canBuyFromPlayers,
        ];
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stSellCard() {
        $playerId = self::getActivePlayerId();

        // metamorph
        $countMetamorph = $this->countCardOfType($playerId, 26);

        if ($countMetamorph < 1) { // no needto check remaining cards, if player got metamoph he got cards to sell
            $this->gamestate->nextState('endTurn');
        }
    }
}
