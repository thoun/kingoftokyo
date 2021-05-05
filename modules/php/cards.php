<?php

namespace KOT\States;

trait CardsTrait {

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////


    function initCards() {
        $cards = [];
        
        for( $value=1; $value<=48; $value++ ) { // keep
            if (in_array($value, $this->TEMP_DONE_KEEP_CARDS)) { // TODO remove filter       
                $cards[] = ['type' => $value, 'type_arg' => $this->cardsCosts[$value], 'nbr' => 1];
            }
        }
        
        for( $value=101; $value<=118; $value++ ) { // discard
            $cards[] = ['type' => $value, 'type_arg' => $this->cardsCosts[$value], 'nbr' => 1];
        }
            
        // $this->cards->createCards( array_slice($cards, count($cards) - 10, 10), 'deck' );
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
                $this->applyGetHealth($playerId, 2); 
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
                $this->applyGetPoints($playerId, 3);
                break;
            case 102:
                $this->applyGetPoints($playerId, 2);
                break;
            case 103:
                $this->applyGetPoints($playerId, 1);
                break;
            case 104: 
                $this->applyGetPoints($playerId, 2);
                if (!$this->inTokyo($playerId)) {
                    // take control of Tokyo
                    if ($this->isTokyoEmpty(false)) {
                        $this->moveToTokyo($playerId, false);
                    } else if ($this->tokyoBayUsed() && $this->isTokyoEmpty(true)) {
                        $this->moveToTokyo($playerId, true);
                    } else {
                        // we force Tokyo city player out
                        $this->leaveTokyo($this->getPlayerIdInTokyoCity());
                        $this->moveToTokyo($playerId, false);
                    }
                }
                break;
            case 105:
                $this->applyGetEnergy($playerId, 9);
                break;
            case 106: case 107:
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $this->applyLosePoints($otherPlayerId, 5);
                }
                break;
            case 108: 
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $this->applyDamage($otherPlayerId, 2, $playerId);
                }
                break;
            case 109: 
                $this->setGameStateValue('playAgainAfterTurn', 1);
                break;
            case 110: 
                $this->applyGetPoints($playerId, 2);
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $this->applyDamage($otherPlayerId, 3, $playerId);
                }
                break;
            case 111:
                $this->applyGetHealth($playerId, 2);
                break;
            case 112: 
                $playersIds = $this->getPlayersIds();
                foreach ($playersIds as $pId) {
                    $this->applyDamage($pId, 3, $playerId);
                }
                break;
            case 113: 
                $this->applyGetPoints($playerId, 5);
                $this->applyDamage($playerId, 4, $playerId);
                break;
            case 114:
                $this->applyGetPoints($playerId, 2);
                $this->applyDamage($playerId, 2, $playerId);
                break;
            case 115:
                $this->applyGetPoints($playerId, 2);
                $this->applyGetHealth($playerId, 3);
                break;
            case 116:
                $this->applyGetPoints($playerId, 4);
                break;
            case 117:
                $this->applyGetPoints($playerId, 4);
                $this->applyDamage($playerId, 3, $playerId);
                break;
            case 118: 
                $this->applyGetPoints($playerId, 2);
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $energy = $this->getPlayerEnergy($otherPlayerId);
                    $lostEnergy = floor($energy / 2);
                    $this->applyLoseEnergy($otherPlayerId, $lostEnergy);
                }
                break;
            case 119:
                $count = $this->cards->countCardInLocation('hand', $player_id);
                $this->applyGetPoints($playerId, $count);
                $this->applyDamage($playerId, $count, $playerId);
                
                break;
            case 120:
        }
    }

    function hasCardByType($playerId, $cardType, $includeMimick = true) {
        return $this->cards->getCardsOfTypeInLocation($cardType, null, 'hand', $playerId);
        // TODO mimick
    }

    function countExtraHead($playerId) {
        $extraHead = 0;
        if ($this->hasCardByType($playerId, 13)) { $extraHead++; };
        if ($this->hasCardByType($playerId, 14)) { $extraHead++; };
        return $extraHead;
    }

    function canBuyCard($playerId, $cardCost) {
        // alien origin
        return $this->hasCardByType($playerId, 2) ? $cardCost - 1 : $cardCost;
    }

    function applyItHasAChild($playerId) {
        // discard all cards
        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $playerId));
        $this->cards->moveAllCardsInLocation('hand', 'discard', $playerId);        
        self::notifyAllPlayers("removeCards", '', [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'cards' => $cards,
        ]);

        // lose all stars
        $points = 0;
        self::DbQuery("UPDATE player SET `player_score` = $points where `player_id` = $playerId");
        self::notifyAllPlayers('points','', [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'points' => $points,
        ]);

        // get back to 10 heart
        $health = 10;
        self::DbQuery("UPDATE player SET `player_health` = $health where `player_id` = $playerId");
        self::notifyAllPlayers('health', '', [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'health' => $health,
        ]);
    }

    function applyBatteryMonster(int $playerId) {
        $energyOnBatteryMonster = intval(self::getGameStateValue('energyOnBatteryMonster')) - 2;
        self::setGameStateValue('energyOnBatteryMonster', $energyOnBatteryMonster);

        $this->applyGetEnergyIgnoreCards($playerId, 2);

        if ($energyOnBatteryMonster <= 0) {
            $card = $this->getCardFromDb($this->cards->getCardsOfType(28)[0]);
            $this->cards->moveCard($card->id, 'discard');        
            self::notifyAllPlayers("removeCards", '', [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'cards' => [$card],
            ]);
        }
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */

    function buyCard(int $id) {
        $playerId = self::getActivePlayerId();

        $card = $this->getCardFromDb($this->cards->getCard($id));

        if ($this->canBuyCard($playerId, $card->cost)) {
            throw new \Error('Not enough energy');
        }

        $cost = $card->cost;
        self::DbQuery("UPDATE player SET `player_energy` = `player_energy` - $cost where `player_id` = $playerId");

        // media friendly
        if ($this->hasCardByType($playerId, 9)) {
            // TOCHECK can it apply on itself ? considered No
            $this->applyGetPoints($playerId, 1);
        }

        $this->cards->moveCard($id, 'hand', $playerId);

        $newCard = $this->getCardFromDb($this->cards->pickCardForLocation('deck', 'table'));

        self::notifyAllPlayers("buyCard", clienttranslate('${player_name} pick card'), [
            'playerId' => $playerId,
            'player_name' => self::getActivePlayerName(),
            'card' => $card,
            'newCard' => $newCard,
            'energy' => $this->getPlayerEnergy($playerId),
        ]);

        $this->applyEffects($card, $playerId);

        // cards effects may eliminate players
        $endGame = $this->eliminatePlayers($playerId);

        if ($endGame) {
            $this->gamestate->nextState('endGame');
        } else {
            $this->gamestate->nextState('pick');
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

        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('table'));
        
        $disabledCards = array_values(array_filter($cards, function ($card) use ($playerId) { return !$this->canBuyCard($playerId, $card->cost); }));
        $disabledIds = array_map(function ($card) { return $card->id; }, $disabledCards);
    
        // return values:
        return [
            'disabledIds' => $disabledIds,
        ];
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

}
