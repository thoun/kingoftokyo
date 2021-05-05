<?php

namespace KOT\States;

trait PlayerTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */

    function endTurn() {
        $this->gamestate->nextState('endTurn');
    }

    function stayInTokyo() {
        $playerId = self::getCurrentPlayerId();

        self::notifyAllPlayers("stayInTokyo", clienttranslate('${player_name} chooses to stay in Tokyo'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
        ]);
    
        // Make this player unactive now (and tell the machine state to use transtion "resume" if all players are now unactive
        $this->gamestate->setPlayerNonMultiactive($playerId, "resume");
    }

    function actionLeaveTokyo() {
        $playerId = self::getCurrentPlayerId();

        $this->leaveTokyo($playerId);
    
        // Make this player unactive now (and tell the machine state to use transtion "resume" if all players are now unactive
        $this->gamestate->setPlayerNonMultiactive($playerId, "resume");
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stStartTurn() {
        $playerId = self::getActivePlayerId();

        self::setGameStateValue('damageDoneByActivePlayer', 0);

        // apply monster effects

        // battery monster
        if ($this->hasCardByType($playerId, 28)) {
            $this->applyBatteryMonster($playerId);
        }

        // apply in tokyo at start

        if ($this->inTokyo($playerId)) {
            // start turn in tokyo
            $incScore = 2;

            // urbavore
            if ($this->hasCardByType($playerId, 46)) {
                $incScore++;
            }

            $this->applyGetPointsIgnoreCards($playerId, $incScore, true);
            self::notifyAllPlayers('points', _('${player_name} starts turn in Tokyo and wins ${deltaPoints} points'), [
                'playerId' => $playerId,
                'player_name' => self::getActivePlayerName(),
                'points' => $this->getPlayerScore($playerId),
                'deltaPoints' => $incScore,
            ]);
        }

        // throw dices

        self::setGameStateValue('throwNumber', 1);
        self::DbQuery( "UPDATE dice SET `dice_value` = 0, `locked` = false" );

        $this->throwDices($playerId);

        $this->gamestate->nextState('throw');
    }

    function stLeaveTokyo() {
        $this->gamestate->setPlayersMultiactive($this->getPlayersIdsInTokyo(), 'resume');
    }

    function stEnterTokyo() {
        $playerId = self::getActivePlayerId();
        if ($this->isTokyoEmpty(false)) {
            $this->moveToTokyo($playerId, false);
        } else if ($this->tokyoBayUsed() && $this->isTokyoEmpty(true)) {
            $this->moveToTokyo($playerId, true);
        }

        if ($this->getMaxPlayerScore() >= MAX_POINT) {
            $this->gamestate->nextState('endGame');
        } else {
            $this->gamestate->nextState('next');
        }
    }

    function stEndTurn() {
        $playerId = self::getActivePlayerId();

        // apply end of turn effects

        // rooting for the underdog
        // TOCHECK is it applied before other end of turn monsters (it may change the fewest Stars) ? considered Yes
        // TOCHECK is it applied if equality in fewest Star ? considered Yes
        if ($this->hasCardByType($playerId, 39) && $this->isFewestStars($playerId)) {
            $this->applyGetPoints($playerId, 1);
        }

        // energy hoarder
        if ($this->hasCardByType($playerId, 11)) {
            $playerEnergy = $this->getPlayerEnergy($playerId);
            $points = floor($playerEnergy / 6);
            $this->applyGetPoints($playerId, $points);
        }

        // herbivore
        if ($this->hasCardByType($playerId, 21) && intval(self::getGameStateValue('damageDoneByActivePlayer')) == 0) {
            $this->applyGetPoints($playerId, 1);
        }

        // solar powered
        if ($this->hasCardByType($playerId, 42) && $this->getPlayerEnergy($playerId) == 0) {
            $this->applyGetEnergy($playerId, 1);
        }

        // remove discard cards

        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $playerId));
        $discardCards = array_filter($cards, function($card) { return $card->type >= 100; });
        $discardCardsIds = array_map(function ($card) { return $card->id; }, $discardCards);
        $this->cards->moveCards($discardCardsIds, 'discard');

        $this->gamestate->nextState('nextPlayer');
    }

    function stNextPlayer() {        
        $player_id = self::getActivePlayerId();

        self::incStat(1, 'turns_number');
        self::incStat(1, 'turns_number', $player_id);

        if (intval($this->getGameStateValue('playAgainAfterTurn')) == 1) { // extra turn for current player              
            $this->setGameStateValue('playAgainAfterTurn', 0);
        } else {
            $player_id = self::activeNextPlayer();
        }
        self::giveExtraTime($player_id);

        if ($this->getMaxPlayerScore() >= MAX_POINT) {
            $this->gamestate->nextState('endGame');
        } else {
            $this->gamestate->nextState('nextPlayer');
        }
    }
}
