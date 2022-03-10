<?php

namespace KOT\States;

trait EvolutionCardsActionTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */  

    function applyChooseEvolutionCard(int $playerId, int $id) {
        $topCards = $this->pickEvolutionCards($playerId);
        $card = $this->array_find($topCards, fn($topCard) => $topCard->id == $id);
        if ($card == null) {
            throw new \BgaUserException('Evolution card not available');
        }
        $otherCard = $this->array_find($topCards, fn($topCard) => $topCard->id != $id);

        $this->evolutionCards->moveCard($id, 'hand', $playerId);
        $this->evolutionCards->moveCard($otherCard->id, 'dicard'.$playerId);

        $this->notifNewEvolutionCard($playerId, $card);
        
    } 

    function chooseEvolutionCard(int $id) {
        $this->checkAction('chooseEvolutionCard');

        $playerId = $this->getActivePlayerId();

        $this->applyChooseEvolutionCard($playerId, $id);

        $nextState = intval($this->getGameStateValue(STATE_AFTER_RESOLVE));
        $this->gamestate->jumpToState($nextState);
    }

    function playEvolution(int $id) {
        $playerId = $this->getCurrentPlayerId();

        $card = $this->getEvolutionCardFromDb($this->evolutionCards->getCard($id));

        // TODOPU check if evolution can be played now

        if (in_array($card->type, $this->AUTO_DISCARDED_EVOLUTIONS)) {
            $this->evolutionCards->moveCard($card->id, 'dicard'.$playerId);
        } else {
            $this->evolutionCards->moveCard($card->id, 'table', $playerId);
        }
        
        $damages = $this->applyEvolutionEffects($card->type, $playerId);

        $this->notifyAllPlayers("playEvolution", clienttranslate('${player_name} plays ${card_name}'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'card' => $card,
            'card_name' => 3000 + $card->type,
        ]);

        // TODOPU handle damages
    }
}
