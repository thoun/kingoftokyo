<?php

namespace KOT\States;

trait EvolutionCardsStateTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stBeforeStartTurn() {
        if (!$this->isPowerUpExpansion()) { // TODOPU Skip if no monster can have a card of this kind ? don't forget mutant evolution
            $this->goToState(ST_START_TURN);
        }
    }
    
}
