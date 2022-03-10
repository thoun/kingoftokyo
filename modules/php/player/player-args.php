<?php

namespace KOT\States;

require_once(__DIR__.'/../objects/damage.php');

use KOT\Objects\Damage;

trait PlayerArgTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    function argLeaveTokyo() {
        $smashedPlayersInTokyo = $this->getGlobalVariable(SMASHED_PLAYERS_IN_TOKYO, true);
        $jetsPlayers = [];
        $canYieldTokyo = [];

        foreach($smashedPlayersInTokyo as $smashedPlayerInTokyo) {
            if ($this->countCardOfType($smashedPlayerInTokyo, JETS_CARD) > 0) {
                $jetsPlayers[] = $smashedPlayerInTokyo;
            }

            $canYieldTokyo[$smashedPlayerInTokyo] = $this->canYieldTokyo($smashedPlayerInTokyo);
        }

        $jetsDamage = null;
        if (count($jetsPlayers) > 0) {
            $jetsDamages = $this->getGlobalVariable(JETS_DAMAGES);
            if (count($jetsDamages) > 0) {
                $jetsDamage = $jetsDamages[0]->damage;
            }
        }

        return [
            'jetsPlayers' => $jetsPlayers,
            'jetsDamage' => $jetsDamage,
            '_private' => [ 
                $this->getActivePlayerId() => [       // not "active" as it actually means current
                    'skipBuyPhase' => boolval($this->getGameStateValue(SKIP_BUY_PHASE)),
                ]
            ],
            'canYieldTokyo' => $canYieldTokyo,
        ];
    }

}
