<?php

namespace KOT\States;

use Bga\Games\KingOfTokyo\Objects\Context;

trait CurseCardsStateTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stGiveSymbolToActivePlayer() {
        if ($this->getPlayer($this->getActivePlayerId())->eliminated) {
            $this->goToState(ST_INITIAL_DICE_ROLL);
            return;
        }

        $this->gamestate->setPlayersMultiactive([$this->anubisExpansion->getPlayerIdWithGoldenScarab()], '', true);
    }

    function stDiscardDie() {
        $playerId = $this->getActivePlayerId();
        
        if (count($this->getPlayerRolledDice($playerId, true, false, false)) == 0) {
            $this->gamestate->nextState('next');
        }
    }

    function stRerollDice() {
        $playerId = $this->anubisExpansion->getRerollDicePlayerId();

        $this->gamestate->setPlayersMultiactive([$playerId], 'end', true);
    }
}
