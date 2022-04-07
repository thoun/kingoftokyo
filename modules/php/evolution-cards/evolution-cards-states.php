<?php

namespace KOT\States;

require_once(__DIR__.'/../objects/question.php');

use KOT\Objects\Question;

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

    function stQuestionsBeforeStartTurn() {
        $playerId = $this->getActivePlayerId();

        $unusedBambooSupplyCard = $this->getFirstUnusedBambooSupply($playerId);
        if ($unusedBambooSupplyCard != null) {
            $question = new Question(
                'BambooSupply',
                /* client TODOPU translate(*/'${actplayer} can put or take [Energy]'/*)*/,
                /* client TODOPU translate(*/'${you} can put or take [Energy]'/*)*/,
                [$playerId],
                ST_QUESTIONS_BEFORE_START_TURN,
                [ 'canTake' => $unusedBambooSupplyCard->tokens > 0 ]
            );
            $this->setQuestion($question);
            $this->gamestate->setPlayersMultiactive([$playerId], 'next', true);

            $this->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
            return;
        }

        $unsetFreezeRayCard = $this->getFirstUnsetFreezeRay($playerId);
        if ($unsetFreezeRayCard != null) {
            $ownerId = $this->getEvolutionOwnerId($unsetFreezeRayCard);
            if ($playerId != $ownerId) {
                $question = new Question(
                    'FreezeRay',
                    /* client TODOPU translate(*/'${actplayer} must choose a die face that will have no effect that turn'/*)*/,
                    /* client TODOPU translate(*/'${you} must choose a die face that will have no effect that turn'/*)*/,
                    [$ownerId],
                    ST_QUESTIONS_BEFORE_START_TURN,
                    [ 'card' => $unsetFreezeRayCard ]
                );
                $this->setQuestion($question);
                $this->gamestate->setPlayersMultiactive([$ownerId], 'next', true);

                $this->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
                return;
            }
        }

        $this->goToState(ST_START_TURN);
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
