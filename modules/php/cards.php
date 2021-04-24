<?php

namespace KOT\States;

trait CardsTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */

    function pickCard(int $id) {

        $this->gamestate->nextState('pick');
    }

    function renewCards() {
        $playerId = self::getActivePlayerId();

        if ($this->getPlayerEnergy($playerId) < 2) {
            throw new Error('Not enough energy');
        }

        self::DbQuery("UPDATE player SET `player_energy` = `player_energy` - 2 where `player_id` = $playerId");

        $this->cards->moveAllCardsInLocation('table', 'discard');
        $cards = $this->getCardsFromDb($this->cards->pickCardsForLocation(3, 'deck', 'table'));

        self::notifyAllPlayers( "renewCards", clienttranslate('${player_name} renew visible cards'), [
            'playerId' => $playerId,
            'player_name' => self::getActivePlayerName(),
            'cards' => $cards,
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

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stPickCard() {
        // TODO remove
        //$this->gamestate->nextState('dontPick');
    }
}
