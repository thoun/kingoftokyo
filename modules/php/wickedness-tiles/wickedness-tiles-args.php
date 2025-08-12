<?php

namespace KOT\States;

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
        $playerId = $this->getActivePlayerId();
        
        $level = $this->wickednessExpansion->canTakeWickednessTile($playerId);
        $tableTiles = $this->wickednessTiles->getTable($level);

        $dice = $this->getPlayerRolledDice($playerId, false, false, false);
        $canHealWithDice = $this->canHealWithDice($playerId);
    
        return [
            'level' => $level,
            'canTake' => count($tableTiles) > 0,

            'dice' => $dice,
            'canHealWithDice' => $canHealWithDice,
            'frozenFaces' => $this->frozenFaces($playerId),
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
