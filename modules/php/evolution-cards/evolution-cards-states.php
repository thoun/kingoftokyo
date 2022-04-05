<?php

namespace KOT\States;

trait EvolutionCardsStateTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stNextPickEvolutionForDeck() {
        $turn = intval($this->getGameStateValue(MUTANT_EVOLUTION_TURN));
        if ($turn === 0) {
            // give 8 random evolutions to each players mutant deck
            $playersIds = $this->getPlayersIds();
            foreach($playersIds as $playerId) {
                $this->evolutionCards->moveAllCardsInLocation('deck'.$playerId, 'mutantdeck');
            }
            $this->evolutionCards->shuffle('mutantdeck');
            foreach($playersIds as $index => $playerId) {
                $this->evolutionCards->pickCardsForLocation(8, 'mutantdeck', 'mutant'.$index);
            }
        }

        if ($turn >= 8) {
            $this->goToState($this->redirectAfterPickEvolutionDeck());
        } else {
            $this->setGameStateValue(MUTANT_EVOLUTION_TURN, $turn + 1);

            $this->gamestate->setAllPlayersMultiactive();
            $this->goToState(ST_MULTIPLAYER_PICK_EVOLUTION_DECK);
        }
    }

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
            // applyBuyCard do the redirection
            $this->applyBuyCard($cardBeingBought->playerId, $cardBeingBought->cardId, $cardBeingBought->from, false);
        } else {
            $this->goToState(ST_PLAYER_BUY_CARD);
        }
    }

    function stAfterAnswerQuestion() {
        $question = $this->getQuestion();

        if ($question->code === 'MegaPurr') {
            $this->removeStackedStateAndRedirect();
        } else {
            throw new \BgaVisibleSystemException("Question code not handled: ".$question->code);
        }
    }
    
}
