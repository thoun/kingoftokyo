<?php

namespace KOT\States;

trait WickednessTilesActionTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */
   
  	
    public function takeWickednessTile(int $id) {
        $this->checkAction('takeWickednessTile');

        $playerId = self::getCurrentPlayerId();

        $level = $this->canTakeWickednessTile($playerId);
        $tile = $this->getWickednessTileFromDb($this->wickednessTiles->getCard($id));
        $this->wickednessTiles->moveCard($id, 'hand', $playerId);

        self::notifyAllPlayers("takeWickednessTile", clienttranslate('${player_name} takes ${card_name}'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'tile' => $tile,
            'card_name' => 2000 + $tile->type,
            'level' => $level,
        ]);

        self::incStat(1, 'wickednessTilesTaken', $playerId);

        self::DbQuery("UPDATE player SET `player_take_wickedness_tile` = 0 where `player_id` = $playerId");

        $damages = $this->applyWickednessTileEffect($tile, $playerId);

        $redirects = false;
        $redirectAfterTakeTile = $this->getRedirectAfterResolveNumberDice();

        if ($damages != null && count($damages) > 0) {
            $redirects = $this->resolveDamages($damages, $redirectAfterTakeTile);
        }

        if (!$redirects) {
            if ($tile->type === FLUXLING_WICKEDNESS_TILE) {
                $this->gamestate->nextState('chooseMimickedCard');
            } else {
                $this->jumpToState($redirectAfterTakeTile, $playerId);
            }
        }
    }
  	
    public function skipTakeWickednessTile() {
        $this->checkAction('skipTakeWickednessTile');

        $playerId = self::getCurrentPlayerId();

        self::DbQuery("UPDATE player SET `player_take_wickedness_tile` = 0 where `player_id` = $playerId");

        $this->jumpToState($this->getRedirectAfterResolveNumberDice());
    }

    function chooseMimickedCardWickednessTile(int $mimickedCardId) {
        $this->checkAction('chooseMimickedCardWickednessTile');

        $playerId = self::getActivePlayerId();

        $card = $this->getCardFromDb($this->cards->getCard($mimickedCardId));        
        if ($card->type > 100 || $card->type == MIMIC_CARD) { // TODOWI can we mimic mimic with tile ?
            throw new \BgaUserException("You can only mimic Keep cards");
        }

        $this->setMimickedCardId(FLUXLING_WICKEDNESS_TILE, $playerId, $mimickedCardId);

        // TODOWI can add smashes !!! change smash count and complete destruction

        $this->jumpToState($this->getRedirectAfterResolveNumberDice());
    }

    function changeMimickedCardWickednessTile(int $mimickedCardId) {
        $this->checkAction('changeMimickedCardWickednessTile');

        $playerId = self::getActivePlayerId();

        $card = $this->getCardFromDb($this->cards->getCard($mimickedCardId));        
        if ($card->type > 100 || $card->type == MIMIC_CARD) { // TODOWI can we mimic mimic with tile ?
            throw new \BgaUserException("You can only mimic Keep cards");
        }

        $this->setMimickedCardId(FLUXLING_WICKEDNESS_TILE, $playerId, $mimickedCardId);

        $this->jumpToState($this->redirectAfterChangeMimickWickednessTile($playerId));
    }

    function skipChangeMimickedCardWickednessTile($skipActionCheck = false) {
        if (!$skipActionCheck) {
            $this->checkAction('skipChangeMimickedCardWickednessTile');
        }

        $playerId = self::getActivePlayerId();

        $this->jumpToState($this->redirectAfterChangeMimickWickednessTile($playerId));
    }    
}
