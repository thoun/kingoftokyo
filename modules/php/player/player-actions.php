<?php

namespace KOT\States;

require_once(__DIR__.'/../objects/damage.php');

use KOT\Objects\Damage;

trait PlayerActionTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */

    function endTurn($skipActionCheck = false) {
        if (!$skipActionCheck) {            
            $this->checkAction('endTurn');
        }
        
        $playerId = $this->getActivePlayerId();
        $this->removeDiscardCards($playerId);
   
        $this->gamestate->nextState('endTurn');
    }

    function notifStayInTokyo($playerId) {
        $this->notifyAllPlayers("stayInTokyo", clienttranslate('${player_name} chooses to stay in Tokyo'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
        ]);
    }

    function stayInTokyo() {
        $this->checkAction('stay');

        $playerId = $this->getCurrentPlayerId();

        $this->notifStayInTokyo($playerId);

        if ($this->isPowerUpExpansion()) {
            $countBlackDiamond = $this->countEvolutionOfType($playerId, BLACK_DIAMOND_EVOLUTION);
            if ($countBlackDiamond > 0) {
                $this->applyGetPoints($playerId, $countBlackDiamond, 3000 + BLACK_DIAMOND_EVOLUTION);
            }
        }
        
        // Make this player unactive now (and tell the machine state to use transtion "resume" if all players are now unactive
        $this->gamestate->setPlayerNonMultiactive($playerId, "resume");
    }

    function actionLeaveTokyo(/*int | null*/ $useCard) {
        $this->checkAction('leave');

        $playerId = $this->getCurrentPlayerId();

        if (!$this->canYieldTokyo($playerId)) {
            throw new \BgaUserException('Impossible to yield Tokyo');
        }

        $this->applyActionLeaveTokyo($playerId, $useCard);
    }

    function applyActionLeaveTokyo(int $playerId, /*int | null*/ $useCard) {
        
        if ($this->leaveTokyo($playerId, false, $useCard)) {
            $this->addLeaverWithBurrowingOrUnstableDNA($playerId);
        }
    
        // Make this player unactive now (and tell the machine state to use transtion "resume" if all players are now unactive
        $this->gamestate->setPlayerNonMultiactive($playerId, "resume");
    }

    function setSkipBuyPhase(bool $skipBuyPhase) {
        if ($this->getCurrentPlayerId() == $this->getActivePlayerId()) {
            $this->setGameStateValue(SKIP_BUY_PHASE, $skipBuyPhase ? 1 : 0);
        }
        
        // dummy notif so player gets back hand
        $this->notifyPlayer($this->getActivePlayerId(), "setSkipBuyPhase", '', []);
    }

    function useCultist($diceIds) {
        $this->checkAction('useCultist');

        $playerId = $this->getActivePlayerId();

        if ($this->getPlayerCultists($playerId) == 0) {
            throw new \BgaUserException('No cultist');
        }
        
        $this->applyLoseCultist($playerId, clienttranslate('${player_name} use a Cultist to gain 1 extra roll'));
        $this->incStat(1, 'cultistReroll', $playerId);
        
        $extraRolls = intval($this->getGameStateValue(EXTRA_ROLLS)) + 1;
        $this->setGameStateValue(EXTRA_ROLLS, $extraRolls);

        $this->rethrowDice($diceIds);
    }

}
