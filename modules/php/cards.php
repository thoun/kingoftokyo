<?php

namespace KOT\States;

trait CardsTrait {

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////

    function applyEffects($card, $playerId) {
        $type = $card->type;

        switch($type) {
            // KEEP
            case 1: return _("Deal 1 extra damage each turn (even when you don't otherwise attack).");
            case 2: return _("Buying cards costs you 1 less [Energy].");
            case 3: return _("Gain 1[Star] when you attack.");
            case 4: return _("Ignore damage of 1.");
            case 5: return _("You can always reroll any [3] you have.");
            case 6: return _("Deal 1 extra damage on Tokyo. Deal 1 damage when yielding Tokyo to the monster taking it.");
            case 7: return _("If you take damage roll a die for each damage point. On a [Heart] you do not take that damage point.");
            case 8: return _("If you roll [1][2][3][Heart][Attack][Energy] gain 9[Star] in addition to the regular results.");
            case 9: return _("Gain 1[Star] whenever you buy a card.");
            case 10: return _("Gain 3[Star] every time a monster's [Heart] goes to 0.");
            case 11: return _("You gain 1[Star] for every 6[Energy] you have at the end of your turn.");
            case 12: return _("Your maximum [Heart] is increased by 2. Gain 2[Heart] when you get this card.");
            case 13: case 14: return _("You get 1 extra die.");
            case 15: return _("Your neighbors take 1 extra damage when you deal damage");
            case 16: return _("On a turn where you score [1][1][1], you can take another turn with one less die.");
            case 17: return _("When you gain any [Energy] gain 1 extra [Energy].");
            case 18: return _("You have one extra reroll each turn.");
            case 19: return _("When scoring [1][1][1] gain 2 extra [Star].");
            case 20: return _("You can heal other monsters with your [Heart] results. They must pay you 2[Energy] for each damage you heal (or their remaining [Energy] if they haven't got enough.");
            case 21: return _("Gain 1[Star] on your turn if you don't damage anyone.");
            case 22: return _("You can change one of your dice to a [1] each turn.");
            case 23: return _("If you are eliminated discard all your cards and lose all your [Star], Heal to 20[Heart] and start again.");
            case 24: return _("You suffer no damage when yielding Tokyo.");
            case 25: return _("When purchasing cards you can peek at and purchase the top card of the deck.");
            case 26: return _("At the end of your turn you can discard any keep cards you have to receive the [Energy] they were purchased for.");
            case 27: return _("Choose a card any monster has in play and put a mimic counter on it. This card counts as a duplicate of that card as if it just had been bought. Spend 1[Energy] at the start of your turn to change the power you are mimicking.");
            case 28: return _("When you purchase this put as many [Energy] as you want on it from your reserve. Match this from the bank. At the start of each turn take 2[Energy] off and add them to your reserve. When there are no [Energy] left discard this card.");
            case 29: return _("Your attacks damage all other monsters.");
            case 30: return _("Once each turn you can score [1][2][3] for 2[Star]. You can use these dice in other combinations.");
            case 31: return _("Whenever a new card is revealed you have the option of purchasing it as soon as it is revealed.");
            case 32: return _("You can purchase cards from other monsters. Pay them the [Energy] cost.");
            case 33: return _("Change one die to any result. Discard when used.");
            case 34: return _("When you score [2][2][2] also deal 2 damage.");
            case 35: return _("When you deal damage to monsters give them a poison counter. Monsters take 1 damage for each poison counter they have at the end of their turn. You can get rid of a poison counter with a [Heart] (that [Heart] doesn't heal a damage also).");
            case 36: return _("You can reroll a die of each other monster once each turn. If the reroll is [Heart] discard this card.");
            case 37: return _("Spend 2[Energy] at any time to heal 1 damage.");
            case 38: return _("When you heal, heal 1 extra damage.");
            case 39: return _("At the end of a turn when you have the fewest [Star] gain 1 [Star].");
            case 40: return _("When you deal damage to monsters give them a shrink counter. A monster rolls one less die for each shrink counter. You can get rid of a shrink counter with a [Heart] (that [Heart] doesn't heal a damage also).");
            case 41: return _("This card starts with 3 charges. Spend a charge for an extra reroll. Discard this card when all charges are spent.");
            case 42: return _("At the end of your turn gain 1[Energy] if you have no [Energy].");
            case 43: return _("When you attack deal 1 extra damage.");
            case 44: return _("You can spend 2[Energy] to change one of your dice to any result.");
            case 45: return _("Spend 1[Energy] to get 1 extra reroll.");
            case 46: return _("Gain 1 extra [Star] when beginning the turn in Tokyo. Deal 1 extra damage when dealing any damage from Tokyo.");
            case 47: return _("When you lose 2[Heart] or more gain 1[Energy].");
            case 48: return _("Spend 2[Energy] to negate damage to you for a turn.");
            case 49: return _("When you do damage gain 1[Heart].");
            case 50: return _("The monsters in Tokyo must yield if you damage them.");
            case 51: return _("If someone kills you, Go back to 10[Heart] and lose all your [Star]. If either of you or your killer win, or all other players are eliminated then you both win. If your killer is eliminated then you are also. If you are eliminated a second time this card has no effect.");
            case 52: return _("If you suffer damage the monster that inflicted the damage suffers 1 as well.");
            case 53: return _("Spend 3[Energy] to gain 1[Star].");
            case 54: return _("Once each turn you may spend 1[Energy] to negate 1 damage you are receiving.");
            case 55: return _("On a turn you deal 3 or more damage gain 2[Star].");
            case 56: return _("If you score 4[Star] in a turn, all players roll one less die until your next turn.");
            case 57: return _("If you yield Tokyo you can take any card the recipient has and give him this card.");
            
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
                    $this->applyDamage($otherPlayerId, 2);
                }
                break;
            case 109: 
                $this->setGameStateValue('playAgainAfterTurn', 1);
                break;
            case 110: 
                $this->applyGetPoints($playerId, 2);
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $this->applyDamage($otherPlayerId, 3);
                }
                break;
            case 111:
                $this->applyGetHealth($playerId, 2);
                break;
            case 112: 
                $playersIds = $this->getPlayersIds();
                foreach ($playersIds as $pId) {
                    $this->applyDamage($pId, 3);
                }
                break;
            case 113: 
                $this->applyGetPoints($playerId, 5);
                $this->applyDamage($playerId, 4);
                break;
            case 114:
                $this->applyGetPoints($playerId, 2);
                $this->applyDamage($playerId, 2);
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
                $this->applyDamage($playerId, 3);
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
                $this->applyDamage($playerId, $count);
                
                break;
            case 120:
        }
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */

    function pickCard(int $id) {
        $playerId = self::getActivePlayerId();

        $card = $this->getCardFromDb($this->cards->getCard($id));

        if ($this->getPlayerEnergy($playerId) < $card->cost) {
            throw new \Error('Not enough energy');
        }

        $cost = $card->cost;
        self::DbQuery("UPDATE player SET `player_energy` = `player_energy` - $cost where `player_id` = $playerId");

        $this->cards->moveCard($id, 'hand', $playerId);

        $newCard = $this->getCardFromDb($this->cards->pickCardForLocation('deck', 'table'));

        self::notifyAllPlayers("pickCard", clienttranslate('${player_name} pick card'), [
            'playerId' => $playerId,
            'player_name' => self::getActivePlayerName(),
            'card' => $card,
            'newCard' => $newCard,
            'energy' => $this->getPlayerEnergy($playerId),
        ]);

        $this->applyEffects($card, $playerId);

        $this->gamestate->nextState('pick');
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
    function argPickCard() {
        $playerId = self::getActivePlayerId();
        $playerEnergy = $this->getPlayerEnergy($playerId);

        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('table'));
        
        $disabledCards = array_values(array_filter($cards, function ($card) use ($playerEnergy) { return $card->cost > $playerEnergy; }));
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
