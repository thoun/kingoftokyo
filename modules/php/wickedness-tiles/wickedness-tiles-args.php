<?php

namespace KOT\States;

trait WickednessTilesArgTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    function argTakeWickednessTile() {
        $playerId = $this->getActivePlayerId();
        
        $level = $this->canTakeWickednessTile($playerId);
        $tableTiles = $this->getTableWickednessTiles($level);
    
        return [
            'level' => $level,
            'canTake' => count($tableTiles) > 0,
        ];
    }

    function argChangeMimickedCardWickednessTile() {
        $playerId = $this->getActivePlayerId();
        return $this->getArgChooseMimickedCard($playerId, FLUXLING_WICKEDNESS_TILE);
    }

    function argChooseMimickedCardWickednessTile() {
        $playerId = $this->getActivePlayerId();
        return $this->getArgChooseMimickedCard($playerId, FLUXLING_WICKEDNESS_TILE);
    }

}
