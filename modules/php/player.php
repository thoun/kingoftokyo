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

        if ($this->inTokyo($playerId)) {
            // start turn in tokyo
            $incScore = 2;
            self::DbQuery("UPDATE player SET player_score = player_score + $incScore where `player_id` = $playerId");
        } else if ($this->isTokyoEmpty(false)) {
            $this->moveToTokyo($playerId, false);
        } else if ($this->tokyoBayUsed() && $this->isTokyoEmpty(true)) {
            $this->moveToTokyo($playerId, true);
        }

        self::setGameStateValue('throwNumber', 1);
        self::DbQuery( "UPDATE dice SET `dice_value` = 0" );

        $this->gamestate->nextState('throw');
    }

    function stEndTurn() {
        $this->gamestate->nextState('nextPlayer');
    }

    function stNextPlayer() {        
        $player_id = self::getActivePlayerId();

        self::incStat(1, 'turns_number');
        self::incStat(1, 'turns_number', $player_id);

        $player_id = self::activeNextPlayer();
        self::giveExtraTime($player_id);

        if ($this->getMaxPlayerScore() >= MAX_POINT) {
            $this->gamestate->nextState('endGame');
        } else {
            $this->gamestate->nextState('nextPlayer');
        }
    }
}
