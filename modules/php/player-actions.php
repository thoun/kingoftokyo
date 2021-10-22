<?php

namespace KOT\States;

require_once(__DIR__.'/objects/damage.php');

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
        
        $playerId = self::getActivePlayerId();
        $this->removeDiscardCards($playerId);
   
        $this->gamestate->nextState('endTurn');
    }

    function notifStayInTokyo($playerId) {
        self::notifyAllPlayers("stayInTokyo", clienttranslate('${player_name} chooses to stay in Tokyo'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
        ]);
    }

    function stayInTokyo() {
        $this->checkAction('stay');

        $playerId = self::getCurrentPlayerId();

        $this->notifStayInTokyo($playerId);
        
        // Make this player unactive now (and tell the machine state to use transtion "resume" if all players are now unactive
        $this->gamestate->setPlayerNonMultiactive($playerId, "resume");
    }

    function actionLeaveTokyo() {
        $this->checkAction('leave');

        $playerId = self::getCurrentPlayerId();

        $this->applyActionLeaveTokyo($playerId);
    }

    function applyActionLeaveTokyo(int $playerId) {
        $this->leaveTokyo($playerId);
        
        // burrowing
        $countBurrowing = $this->countCardOfType($playerId, BURROWING_CARD);
        if ($countBurrowing > 0) {
            self::setGameStateValue('loseHeartEnteringTokyo', $countBurrowing);
        }
    
        // Make this player unactive now (and tell the machine state to use transtion "resume" if all players are now unactive
        $this->gamestate->setPlayerNonMultiactive($playerId, "resume");
    }

    function setSkipBuyPhase(bool $skipBuyPhase) {
        if (self::getCurrentPlayerId() != self::getActivePlayerId()) {
            throw new \BgaUserException("You're not the active player");
        }

        self::setGameStateValue(SKIP_BUY_PHASE, $skipBuyPhase ? 1 : 0);
        // dummy notif so player gets back hand
        self::notifyPlayer($this->getActivePlayerId(), "setSkipBuyPhase", '', []);
    }


}
