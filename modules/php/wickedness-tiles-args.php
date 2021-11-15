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
        $playerId = self::getActivePlayerId();
        
        $level = $this->canTakeWickednessTile($playerId);
    
        return [
            'level' => $level,
        ];
    }

}
