<?php

namespace KOT\States;

trait EvolutionCardsStateTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stCardIsBought() {
        $otherPlayersIds = $this->getOtherPlayersIds($this->getActivePlayerId());

        $stepCardsMonsters = array_values(array_unique(array_map(fn($cardId) => floor($cardId / 10), $this->EVOLUTION_TO_PLAY_WHEN_CARD_IS_BOUGHT)));

        $dbResults = $this->getCollectionFromDb("SELECT player_id, player_monster FROM player WHERE player_id IN (".implode(',', $otherPlayersIds).")");
        $playerMonsters = [];
        foreach($dbResults as $dbResult) {
            $playerMonsters[intval($dbResult['player_id'])] = intval($dbResult['player_monster']);
        }

        $playersWithPotentialEvolution = array_values(array_filter($otherPlayersIds, fn($otherPlayer) => in_array($playerMonsters[$otherPlayer], $stepCardsMonsters)));

        $this->gamestate->setPlayersMultiactive($playersWithPotentialEvolution, 'next', true);
    }

    function stAfterCardIsBought() {
        $cardBeingBought = $this->getGlobalVariable(CARD_BEING_BOUGHT);

        if ($cardBeingBought->allowed) {
            $this->applyBuyCard($cardBeingBought->playerId, $cardBeingBought->cardId, $cardBeingBought->from, false);
        }

        $this->goToState(ST_PLAYER_BUY_CARD);
    }
    
}
