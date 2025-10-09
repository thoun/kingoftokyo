<?php

namespace KOT\States;

use Bga\Games\KingOfTokyo\Objects\Context;

use const Bga\Games\KingOfTokyo\FLUXLING_WICKEDNESS_TILE;

trait WickednessTilesActionTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */

    function actChooseMimickedCardWickednessTile(int $id) {
        $playerId = $this->getActivePlayerId();

        $card = $this->powerCards->getItemById($id);        
        if ($card->type > 100) {
            throw new \BgaUserException("You can only mimic Keep cards");
        }
        if ($card->type == MIMIC_CARD) {
            throw new \BgaUserException("You cannot mimic Mimic cards");
        }

        $this->setMimickedCardId(FLUXLING_WICKEDNESS_TILE, $playerId, $id);

        $this->goToState($this->redirectAfterResolveNumberDice());
    }

}
