<?php

namespace KOT\States;

trait RedirectionTrait {
    function goToState(int $nextStateId, /*Damage[] | null*/ $damages = null) {
        $redirects = false;
        
        if ($damages != null && count($damages) > 0) {
            $redirects = $this->resolveDamages($damages, $nextStateId);
        }

        if (!$redirects) {
            $this->jumpToState($nextStateId);
        }
    }

    function redirectAfterStartTurn(int $playerId) {
        if ($this->canChangeMimickedCardWickednessTile()) {
            return ST_PLAYER_CHANGE_MIMICKED_CARD_WICKEDNESS_TILE;
        }
        return $this->redirectAfterChangeMimickWickednessTile($playerId);
    }

    function redirectAfterChangeMimickWickednessTile(int $playerId) {
        if ($this->canChangeMimickedCard()) {
            return ST_PLAYER_CHANGE_MIMICKED_CARD;
        }
        return $this->redirectAfterChangeMimick($playerId);
    }

    function redirectAfterChangeMimick(int $playerId) {
        $playerIdWithGoldenScarab = $this->getPlayerIdWithGoldenScarab();
        if ($this->isAnubisExpansion() && $this->getCurseCardType() == KHEPRI_S_REBELLION_CURSE_CARD && $playerIdWithGoldenScarab != null && $playerId != $playerIdWithGoldenScarab) {
            return ST_MULTIPLAYER_GIVE_SYMBOL_TO_ACTIVE_PLAYER;
        }
        return ST_INITIAL_DICE_ROLL;
    }

    function redirectAfterResolveDice() {
        return ST_RESOLVE_NUMBER_DICE;
    }
    
}
