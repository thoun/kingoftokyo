<?php

namespace KOT\States;

trait CurseCardsStateTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stGiveSymbolToActivePlayer() {
        $this->gamestate->setPlayersMultiactive([$this->getPlayerIdWithGoldenScarab()], '', true);
    }

    function stDiscardDie() {
        $playerId = self::getActivePlayerId();
        
        if (count($this->getPlayerRolledDice($playerId, true, false, false)) == 0) {
            $this->gamestate->nextState('next');
        }
    }
}
