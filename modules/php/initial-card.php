<?php

namespace KOT\States;

trait InitialCardTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////

    function setInitialCard(int $playerId, int $id, object $otherCard) {
        $card = $this->getCardFromDb($this->cards->getCard($id));
        
        $this->cards->moveCard($id, 'hand', $playerId);
        $this->cards->moveCard($otherCard->id, 'costumediscard');

        self::notifyAllPlayers("buyCard", clienttranslate('${player_name} takes ${card_name}'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'card' => $card,
            'card_name' => $card->type,
            'discardCard' =>$otherCard,
        ]);
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */

    function chooseInitialCard(int $id) {
        $this->checkAction('chooseInitialCard');

        $topCards = $this->getCardsFromDb($this->cards->getCardsOnTop(2, 'costumedeck'));
        if (!$this->array_some($topCards, fn($topCard) => $topCard->id == $id)) {
            throw new \BgaUserException('Card not available');
        }
        $otherCard = $this->array_find($topCards, fn($topCard) => $topCard->id != $id);

        $playerId = self::getActivePlayerId();

        $this->setInitialCard($playerId, $id, $otherCard);

        $this->gamestate->nextState('next');
    }

    private function everyPlayerHasCostumeCard() {
        $playersIds = $this->getNonZombiePlayersIds();
        foreach($playersIds as $playerId) {
            $cardsOfPlayer = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $playerId));
            if (!$this->array_some($cardsOfPlayer, fn($card) => $card->type > 200 && $card->type < 300)) {
                return false;
            }
        }
        return true;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    function argChooseInitialCard() {
        return [
            'cards' => $this->getCardsFromDb($this->cards->getCardsOnTop(2, 'costumedeck')),
        ];
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stChooseInitialCard() { 
        if ($this->everyPlayerHasCostumeCard()) {
            $this->gamestate->nextState('start');
        }
    }

    function stChooseInitialCardNextPlayer() {
        $playerId = self::activeNextPlayer();
        self::giveExtraTime($playerId);

        if ($this->everyPlayerHasCostumeCard()) {
            $this->gamestate->nextState('start');
        } else {
            $this->gamestate->nextState('nextPlayer');
        }
    }

}
