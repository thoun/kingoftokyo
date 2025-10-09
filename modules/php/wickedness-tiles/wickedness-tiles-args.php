<?php

namespace KOT\States;

use const Bga\Games\KingOfTokyo\FINAL_PUSH_WICKEDNESS_TILE;
use const Bga\Games\KingOfTokyo\FLUXLING_WICKEDNESS_TILE;

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
    }

    function argChooseMimickedCardWickednessTile() {
        $playerId = $this->getActivePlayerId();
        return $this->getArgChooseMimickedCard($playerId, FLUXLING_WICKEDNESS_TILE);
    }

}
