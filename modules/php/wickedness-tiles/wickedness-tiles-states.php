<?php

namespace KOT\States;

trait WickednessTilesStateTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stTakeWickednessTile() {
        if ($this->autoSkipImpossibleActions()) {
            $playerId = $this->getActivePlayerId();        
            $level = $this->canTakeWickednessTile($playerId);
            $tableTiles = $this->getTableWickednessTiles($level);
        
            if (count($tableTiles) == 0) {
                $this->skipTakeWickednessTile(true);
            }
        }
    }
}
