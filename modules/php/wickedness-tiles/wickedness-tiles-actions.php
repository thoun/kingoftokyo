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

        $playerId = $this->getCurrentPlayerId();

        $level = $this->canTakeWickednessTile($playerId);
        $tile = $this->getWickednessTileFromDb($this->wickednessTiles->getCard($id));
        $this->wickednessTiles->moveCard($id, 'hand', $playerId);

        $this->notifyAllPlayers("takeWickednessTile", clienttranslate('${player_name} takes ${card_name}'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'tile' => $tile,
            'card_name' => 2000 + $tile->type,
            'level' => $level,
        ]);

        // TODOWI $this->incStat(1, 'wickednessTilesTaken', $playerId);

        $this->DbQuery("UPDATE player SET `player_take_wickedness_tile` = 0 where `player_id` = $playerId");

        $damages = $this->applyWickednessTileEffect($tile, $playerId);

        $redirectAfterTakeTile = $this->redirectAfterResolveNumberDice();

        if ($tile->type === FLUXLING_WICKEDNESS_TILE) {
            $countAvailableCardsForMimic = 0;

            $playersIds = $this->getPlayersIds();
            foreach($playersIds as $playerId) {
                $cardsOfPlayer = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $playerId));
                $countAvailableCardsForMimic += count(array_values(array_filter($cardsOfPlayer, fn($card) => $card->type != MIMIC_CARD && $card->type < 100)));
            }

            if ($countAvailableCardsForMimic > 0) {
                $redirectAfterTakeTile = ST_PLAYER_CHOOSE_MIMICKED_CARD_WICKEDNESS_TILE;
            }
        }

        $this->goToState($redirectAfterTakeTile, $damages);
    }
  	
    public function skipTakeWickednessTile() {
        $this->checkAction('skipTakeWickednessTile');

        $playerId = $this->getCurrentPlayerId();

        $this->DbQuery("UPDATE player SET `player_take_wickedness_tile` = 0 where `player_id` = $playerId");

        $this->goToState($this->redirectAfterResolveNumberDice());
    }

    function chooseMimickedCardWickednessTile(int $mimickedCardId) {
        $this->checkAction('chooseMimickedCardWickednessTile');

        $playerId = $this->getActivePlayerId();

        $card = $this->getCardFromDb($this->cards->getCard($mimickedCardId));        
        if ($card->type > 100) {
            throw new \BgaUserException("You can only mimic Keep cards");
        }
        if ($card->type == MIMIC_CARD) {
            throw new \BgaUserException("You cannot mimic Mimic cards");
        }

        $this->setMimickedCardId(FLUXLING_WICKEDNESS_TILE, $playerId, $mimickedCardId);

        $this->goToState($this->redirectAfterResolveNumberDice());
    }

    function changeMimickedCardWickednessTile(int $mimickedCardId) {
        $this->checkAction('changeMimickedCardWickednessTile');

        $playerId = $this->getActivePlayerId();

        $card = $this->getCardFromDb($this->cards->getCard($mimickedCardId));        
        if ($card->type > 100) {
            throw new \BgaUserException("You can only mimic Keep cards");
        }
        if ($card->type == MIMIC_CARD) {
            throw new \BgaUserException("You cannot mimic Mimic cards");
        }

        $this->setMimickedCardId(FLUXLING_WICKEDNESS_TILE, $playerId, $mimickedCardId);

        $this->jumpToState($this->redirectAfterChangeMimickWickednessTile($playerId));
    }

    function skipChangeMimickedCardWickednessTile($skipActionCheck = false) {
        if (!$skipActionCheck) {
            $this->checkAction('skipChangeMimickedCardWickednessTile');
        }

        $playerId = $this->getActivePlayerId();

        $this->jumpToState($this->redirectAfterChangeMimickWickednessTile($playerId));
    }    
}
