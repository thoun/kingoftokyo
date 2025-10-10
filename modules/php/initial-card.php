<?php

namespace KOT\States;

use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\PowerCards\PowerCard;

trait InitialCardTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////

    function setInitialCostumeCard(int $playerId, int $id, PowerCard $otherCard) {
        $card = $this->powerCards->getItemById($id);
        
        $this->powerCards->moveItem($card, 'hand', $playerId);
        $this->powerCards->moveItem($otherCard, 'costumediscard');

        $this->notifyAllPlayers("buyCard", clienttranslate('${player_name} takes ${card_name}'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerNameById($playerId),
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

    private function everyPlayerHasCostumeCard() {
        $playersIds = $this->getNonZombiePlayersIds();
        foreach($playersIds as $playerId) {
            $cardsOfPlayer = $this->powerCards->getPlayerReal($playerId);
            if (!$this->array_some($cardsOfPlayer, fn($card) => $card->type > 200 && $card->type < 300)) {
                return false;
            }
        }
        return true;
    }

    private function everyPlayerHasEvolutionCard() {
        $playersIds = $this->getNonZombiePlayersIds();
        foreach($playersIds as $playerId) {
            if ($this->powerUpExpansion->evolutionCards->countItemsInLocation('hand', $playerId) == 0) {
                return false;
            }
        }
        return true;
    }

}
