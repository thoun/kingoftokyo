<?php

namespace KOT\States;

require_once(__DIR__.'/../Objects/damage.php');

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
        $simianScamperPlayers = [];
        $canYieldTokyo = [];

        $isPowerUpExpansion = $this->powerUpExpansion->isActive();
        foreach($smashedPlayersInTokyo as $smashedPlayerInTokyo) {
            if ($this->countCardOfType($smashedPlayerInTokyo, JETS_CARD) > 0) {
                $jetsPlayers[] = $smashedPlayerInTokyo;
            }
            
            if ($isPowerUpExpansion && $this->countEvolutionOfType($smashedPlayerInTokyo, SIMIAN_SCAMPER_EVOLUTION, true, true) > 0) {
                $simianScamperPlayers[] = $smashedPlayerInTokyo;
            }

            $canYieldTokyo[$smashedPlayerInTokyo] = $this->canYieldTokyo($smashedPlayerInTokyo);
        }

        $jetsDamage = null;
        if (count($jetsPlayers) > 0 || count($simianScamperPlayers) > 0) {
            $jetsDamages = $this->getGlobalVariable(JETS_DAMAGES);
            if (count($jetsDamages) > 0) {
                $jetsDamage = $jetsDamages[0]->damage;
            }
        }

        $activePlayerId = $this->getActivePlayerId();
        $args = [
            'jetsPlayers' => $jetsPlayers,
            'simianScamperPlayers' => $simianScamperPlayers,
            'jetsDamage' => $jetsDamage,
            '_private' => [ 
                $this->getActivePlayerId() => [
                    'skipBuyPhase' => boolval($this->getGameStateValue(SKIP_BUY_PHASE)),
                ]
            ],
            'canYieldTokyo' => $canYieldTokyo,
            'activePlayerId' => $activePlayerId,
        ];

        if ($isPowerUpExpansion) {
            $countChestThumping = $this->countEvolutionOfType($activePlayerId, CHEST_THUMPING_EVOLUTION);
            $anubisWithPharaonicEgo = $this->anubisExpansion->isActive() && $this->getCurseCardType() == PHARAONIC_EGO_CURSE_CARD;
            if ($countChestThumping > 0 && !$anubisWithPharaonicEgo) { // impossible to use Chest Thumping with Pharaonic Ego 
                $smashedPlayersInTokyoStillInTokyo = array_values(array_filter($smashedPlayersInTokyo, fn($pId) => $this->inTokyo($pId)));
                $args = array_merge($args, [
                    'canUseChestThumping' => true,
                    'smashedPlayersInTokyo' => $smashedPlayersInTokyoStillInTokyo,
                ]);
            }
        }

        return $args;
    }

}
