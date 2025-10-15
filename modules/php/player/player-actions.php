<?php

namespace KOT\States;

require_once(__DIR__.'/../Objects/damage.php');

use Bga\GameFramework\Actions\CheckAction;
use Bga\Games\KingOfTokyo\Objects\Context;

/**
 * @mixin \Bga\Games\KingOfTokyo\Game
 */
trait PlayerActionTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */

    function notifStayInTokyo($playerId) {
        $this->notify->all("stayInTokyo", clienttranslate('${player_name} chooses to stay in Tokyo'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerNameById($playerId),
        ]);
    }

    function yieldTokyo(int $playerId, /*int | null*/ $useCard = null) {
        if (!$this->canYieldTokyo($playerId)) {
            throw new \BgaUserException('Impossible to yield Tokyo');
        }
        
        $this->addLeaverWithBurrowingOrUnstableDNA($playerId);
        $this->leaveTokyo($playerId, $useCard);

        $interdimensionalPortalEvolutions = $this->powerUpExpansion->evolutionCards->getPlayerVirtualByType($playerId, INTERDIMENSIONAL_PORTAL_EVOLUTION, true, false);
        if (count($interdimensionalPortalEvolutions) > 0) {
            $interdimensionalPortalEvolutions[0]->applyEffect(new Context($this, $playerId));
            return;
        }

    
        // leaveTokyo may have already made transition with Chest Thumping
        if ($this->gamestate->getCurrentMainStateId() == ST_MULTIPLAYER_LEAVE_TOKYO) {
            $this->gamestate->setPlayerNonMultiactive($playerId, "resume");
        }

        $this->checkOnlyChestThumpingRemaining();
    }

    #[CheckAction(false)]
    function actSetSkipBuyPhase(bool $skipBuyPhase) {
        if ($this->getCurrentPlayerId() == $this->getActivePlayerId()) {
            $this->setGameStateValue(SKIP_BUY_PHASE, $skipBuyPhase ? 1 : 0);
        }
        
        // dummy notif so player gets back hand
        $this->notify->player($this->getActivePlayerId(), "setSkipBuyPhase", '', []);
    }

    #[CheckAction(false)]
    function actUseRapidHealing(int $currentPlayerId) {
        $this->applyRapidHealing($currentPlayerId);

        $this->updateCancelDamageIfNeeded($currentPlayerId);
    }

    #[CheckAction(false)]
    function actUseMothershipSupport(int $currentPlayerId) {
        $this->applyMothershipSupport($currentPlayerId);

        $this->updateCancelDamageIfNeeded($currentPlayerId);
    }

  	
}
