<?php

namespace KOT\States;

trait InitialCardTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////

    function setInitialCostumeCard(int $playerId, int $id, object $otherCard) {
        $card = $this->getCardFromDb($this->cards->getCard($id));
        
        $this->cards->moveCard($id, 'hand', $playerId);
        $this->cards->moveCard($otherCard->id, 'costumediscard');

        $this->notifyAllPlayers("buyCard", clienttranslate('${player_name} takes ${card_name}'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'card' => $card,
            'card_name' => $card->type,
            'discardCard' =>$otherCard,
        ]);
    }

    function isInitialCardDistributionComplete() {
        return ($this->isHalloweenExpansion() && $this->everyPlayerHasCostumeCard()) || ($this->powerUpExpansion->isActive() && $this->everyPlayerHasEvolutionCard());
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */

    function chooseInitialCard(/*int | null*/ $costumeId, /*int | null*/ $evolutionId) {
        $this->checkAction('chooseInitialCard');

        $playerId = $this->getActivePlayerId();

        $args = $this->argChooseInitialCard();

        if ($args['chooseCostume']) {
            if ($costumeId == null || $costumeId == 0) {
                throw new \BgaUserException('No selected Costume card');
            }

            $topCards = $this->getCardsFromDb($this->cards->getCardsOnTop(2, 'costumedeck'));
            if (!$this->array_some($topCards, fn($topCard) => $topCard->id == $costumeId)) {
                throw new \BgaUserException('Card not available');
            }
            $otherCard = $this->array_find($topCards, fn($topCard) => $topCard->id != $costumeId);

            $this->setInitialCostumeCard($playerId, $costumeId, $otherCard);
        }

        if ($args['chooseEvolution']) {
            if ($evolutionId == null || $evolutionId == 0) {
                throw new \BgaUserException('No selected Evolution card');
            }

            $this->applyChooseEvolutionCard($playerId, $evolutionId, true);
        }

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

    private function everyPlayerHasEvolutionCard() {
        $playersIds = $this->getNonZombiePlayersIds();
        foreach($playersIds as $playerId) {
            if ($this->evolutionCards->countItemsInLocation('hand', $playerId) == 0) {
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
        $activePlayerId = $this->getActivePlayerId();

        $chooseCostume = $this->isHalloweenExpansion();
        $chooseEvolution = $this->powerUpExpansion->isActive();

        $args = [
            'chooseCostume' => $chooseCostume,
            'chooseEvolution' => $chooseEvolution,
        ];

        if ($chooseCostume) {
            $args['cards'] = $this->getCardsFromDb($this->cards->getCardsOnTop(2, 'costumedeck'));
        }

        if ($chooseEvolution) {
            $args['_private'] = [
                $activePlayerId => [
                    'evolutions' => $this->pickEvolutionCards($activePlayerId),
                ]
            ];
        }

        return $args;
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stChooseInitialCard() { 
        if ($this->isInitialCardDistributionComplete()) {
            $this->gamestate->nextState('start');
        }
    }

    function stChooseInitialCardNextPlayer() {
        $playerId = $this->activeNextPlayer();
        $this->giveExtraTime($playerId);

        if ($this->isInitialCardDistributionComplete()) {
            $this->gamestate->nextState('start');
        } else {
            $this->gamestate->nextState('nextPlayer');
        }
    }

}
