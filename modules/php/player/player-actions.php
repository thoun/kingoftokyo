<?php

namespace KOT\States;

require_once(__DIR__.'/../Objects/damage.php');

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
   
        $this->goToState($this->redirectAfterSellCard());
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

        $countBullHeaded = $this->countCardOfType($playerId, BULL_HEADED_CARD);
        if ($countBullHeaded > 0) {
            $this->applyGetPoints($playerId, $countBullHeaded, BULL_HEADED_CARD);
        }
        
        // Make this player unactive now (and tell the machine state to use transtion "resume" if all players are now unactive
        $this->gamestate->setPlayerNonMultiactive($playerId, "resume");
    }

    function actionLeaveTokyo(/*int | null*/ $useCard) {
        $this->checkAction('leave');

        $playerId = $this->getCurrentPlayerId();

        $this->yieldTokyo($playerId, $useCard);
    }

    function yieldTokyo(int $playerId, /*int | null*/ $useCard = null) {
        if (!$this->canYieldTokyo($playerId)) {
            throw new \BgaUserException('Impossible to yield Tokyo');
        }
        
        $this->addLeaverWithBurrowingOrUnstableDNA($playerId);
        $this->leaveTokyo($playerId, $useCard);
    
        // leaveTokyo may have already made transition with Chest Thumping
        if (intval($this->gamestate->state_id()) == ST_MULTIPLAYER_LEAVE_TOKYO) {
            $this->gamestate->setPlayerNonMultiactive($playerId, "resume");
        }

        $this->checkOnlyChestThumpingRemaining();
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
