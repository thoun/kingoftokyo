<?php

namespace KOT\States;

use function Bga\Games\KingOfTokyo\debug;

trait WickednessTilesStateTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stTakeWickednessTile() {
        $playerId = $this->getActivePlayerId();  

        // if player is dead async, he can't buy or sell
        if ($this->getPlayer($playerId)->eliminated) {
            $this->skipTakeWickednessTile(true);
            return;
        }

        if ($this->autoSkipImpossibleActions()) {      
            $level = $this->wickednessExpansion->canTakeWickednessTile($playerId);
            $tableTiles = $this->wickednessTiles->getTable($level);
        
            if (count($tableTiles) == 0) {
                $this->skipTakeWickednessTile(true);
            }
        }
    }
}
