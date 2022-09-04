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
            $this->setOwnerIdForAllEvolutions();
            
            $this->goToState($this->redirectAfterPickEvolutionDeck());
        } else {
            $this->setGameStateValue(MUTANT_EVOLUTION_TURN, $turn + 1);

            $this->gamestate->setAllPlayersMultiactive();
            $this->goToState(ST_MULTIPLAYER_PICK_EVOLUTION_DECK);
        }
    }

    function stQuestionsBeforeStartTurn() {
        $playerId = $this->getActivePlayerId();

        $unusedBambooSupplyCard = $this->getFirstUnusedEvolution($playerId, BAMBOO_SUPPLY_EVOLUTION);
        if ($unusedBambooSupplyCard != null) {
            $question = new Question(
                'BambooSupply',
                clienttranslate('${actplayer} can put or take [Energy]'),
                clienttranslate('${you} can put or take [Energy]'),
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
            $ownerId = $unsetFreezeRayCard->ownerId;
            if ($playerId != $ownerId) {
                $question = new Question(
                    'FreezeRay',
                    clienttranslate('${actplayer} must choose a die face that will have no effect that turn'),
                    clienttranslate('${you} must choose a die face that will have no effect that turn'),
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

        $unusedExoticArmsCard = $this->getFirstUnusedEvolution($playerId, EXOTIC_ARMS_EVOLUTION);
        if ($unusedExoticArmsCard != null) {

            $potentialEnergy = $this->getPlayerPotentialEnergy($playerId);

            if ($potentialEnergy >= 2) {
                $question = new Question(
                    'ExoticArms',
                    clienttranslate('${actplayer} can put 2[Energy] on ${card_name}'),
                    clienttranslate('${you} can put 2[Energy] on ${card_name}'),
                    [$playerId],
                    ST_QUESTIONS_BEFORE_START_TURN,
                    [ 
                        'card' => $unusedExoticArmsCard,
                        '_args' => [
                            'card_name' => 3000 + EXOTIC_ARMS_EVOLUTION,
                        ],                        
                    ]
                );
                $this->setQuestion($question);
                $this->gamestate->setPlayersMultiactive([$playerId], 'next', true);

                $this->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
                return;
            }
        }

        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $playerId));
        $superiorAlienTechnologyTokens = $this->getSuperiorAlienTechnologyTokens($playerId);
        $cardsWithSuperiorAlienTechnologyTokens = array_values(array_filter($cards, fn($card) => in_array($card->id, $superiorAlienTechnologyTokens)));
        $usedCardsIds = $this->getUsedCard();
        $cardWithSuperiorAlienTechnologyToken = $this->array_find($cardsWithSuperiorAlienTechnologyTokens, fn($iCard) => !in_array(800 + $iCard->id, $usedCardsIds));

        if ($cardWithSuperiorAlienTechnologyToken != null) {
            $question = new Question(
                'SuperiorAlienTechnology',
                clienttranslate('${actplayer} must roll a die for ${card_name} ([ufoToken] on it)'),
                clienttranslate('${you} must roll a die for ${card_name} ([ufoToken] on it)'),
                [$playerId],
                ST_QUESTIONS_BEFORE_START_TURN,
                [
                    'card' => $cardWithSuperiorAlienTechnologyToken,
                    '_args' => [
                        'card_name' => $cardWithSuperiorAlienTechnologyToken->type,
                    ],
                ]
            );
            $this->setQuestion($question);
            $this->gamestate->setPlayersMultiactive([$playerId], 'next', true);
    
            $this->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
            return;
        }

        $unusedEnergySwordCard = $this->getFirstUnusedEvolution($playerId, ENERGY_SWORD_EVOLUTION);
        if ($unusedEnergySwordCard != null) {

            $potentialEnergy = $this->getPlayerPotentialEnergy($playerId);

            if ($potentialEnergy >= 2) {
                $question = new Question(
                    'EnergySword',
                    clienttranslate('${actplayer} can pay 2[Energy] for ${card_name}'),
                    clienttranslate('${you} can pay 2[Energy] for ${card_name}'),
                    [$playerId],
                    ST_QUESTIONS_BEFORE_START_TURN,
                    [
                        '_args' => [
                            'card_name' => 3000 + ENERGY_SWORD_EVOLUTION,
                        ],
                    ]
                );
                $this->setQuestion($question);
                $this->gamestate->setPlayersMultiactive([$playerId], 'next', true);

                $this->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
                return;
            }
        }

        // must be the last question because it ends the turn
        $unusedTempleEvolutionCard = $this->getFirstUnusedEvolution($playerId, SUNKEN_TEMPLE_EVOLUTION);
        if ($unusedTempleEvolutionCard != null && !$this->inTokyo($playerId)) {

            $question = new Question(
                'SunkenTemple',
                /* client TODODE translate(*/'${actplayer} can pass turn to gain 3[Heart] and 3[Energy]'/*)*/,
                /* client TODODE translate(*/'${you} can pass your turn to gain 3[Heart] and 3[Energy]'/*)*/,
                [$playerId],
                ST_QUESTIONS_BEFORE_START_TURN,
                []
            );
            $this->setQuestion($question);
            $this->gamestate->setPlayersMultiactive([$playerId], 'next', true);
        }
        $this->goToState(ST_START_TURN);
    }

    function stCardIsBought() {
        $otherPlayersIds = $this->getOtherPlayersIds($this->getActivePlayerId());

        $stepCardsMonsters = array_values(array_unique(array_map(fn($cardId) => floor($cardId / 10), $this->EVOLUTION_TO_PLAY_WHEN_CARD_IS_BOUGHT)));

        $dbResults = $this->getCollectionFromDb("SELECT player_id, player_monster FROM player WHERE player_id IN (".implode(',', $otherPlayersIds).")");
        $playerMonsters = [];
        foreach($dbResults as $dbResult) {
            $playerMonsters[intval($dbResult['player_id'])] = intval($dbResult['player_monster']) % 100;
        }

        $playersWithPotentialEvolution = array_values(array_filter($otherPlayersIds, fn($otherPlayer) => in_array($playerMonsters[$otherPlayer], $stepCardsMonsters)));

        $this->gamestate->setPlayersMultiactive($playersWithPotentialEvolution, 'next', true);
    }

    function stAfterCardIsBought() {
        $cardBeingBought = $this->getGlobalVariable(CARD_BEING_BOUGHT);

        if ($cardBeingBought->allowed) {
            // applyBuyCard do the redirection
            $this->applyBuyCard($cardBeingBought->playerId, $cardBeingBought->cardId, $cardBeingBought->from, false, $cardBeingBought->cost, $cardBeingBought->useSuperiorAlienTechnology);
        } else {
            $this->goToState(ST_PLAYER_BUY_CARD);
        }
    }

    function stAfterAnswerQuestion() {
        $question = $this->getQuestion();

        if ($question->code === 'MegaPurr' || $question->code === 'ElectricCarrot') {
            if ($question->code === 'MegaPurr') {
                $this->removeEvolution($question->args->playerId, $question->args->card);
            }

            $this->removeStackedStateAndRedirect();
        } else if ($question->code === 'TargetAcquired' || $question->code === 'LightningArmor') {
            $this->goToState(ST_MULTIPLAYER_AFTER_RESOLVE_DAMAGE);
        } else {
            throw new \BgaVisibleSystemException("Question code not handled: ".$question->code);
        }
    }

    function stBeforeResolveDice() {
        if (!$this->isPowerUpExpansion()) {
            $this->goToState($this->redirectAfterBeforeResolveDice());
            return;
        }

        $playerId = $this->getActivePlayerId();
        $couldPlay = $this->canPlayStepEvolution([$playerId], $this->EVOLUTION_TO_PLAY_BEFORE_RESOLVE_DICE);

        if ($couldPlay) {
            $this->gamestate->setPlayersMultiactive([$playerId], 'next');
        } else {
            $this->goToState($this->redirectAfterBeforeResolveDice());
        }
    }

    function stAfterResolveDamage() {
        $intervention = $this->getDamageIntervention();

        if (!$intervention->targetAcquiredAsked) {
            $askTargetAcquired = $this->askTargetAcquired($intervention->allDamages); // TODOPU when damage fully cancel, remove it from allDamages too

            $intervention->targetAcquiredAsked = true;
            $this->setDamageIntervention($intervention);

            if ($askTargetAcquired) {
                return;
            }
        }

        if (!$intervention->lightningArmorAsked) {
            $askLightningArmor = $this->askLightningArmor($intervention->allDamages); // TODOPU when damage fully cancel, remove it from allDamages too

            $intervention->lightningArmorAsked = true;
            $this->setDamageIntervention($intervention);

            if ($askLightningArmor) {
                return;
            }
        }

        $this->goToState($intervention->endState);
    }

    function stBeforeEndTurn() {
        $playerId = intval($this->getActivePlayerId());
        $this->removeDiscardCards($playerId);

        $playersIds = $this->getPlayersIds();
        $stepCardsMonsters = array_values(array_unique(array_map(fn($cardId) => floor($cardId / 10), $this->EVOLUTION_TO_PLAY_BEFORE_END_MULTI)));

        $dbResults = $this->getCollectionFromDb("SELECT player_id, player_monster FROM player WHERE player_id IN (".implode(',', $playersIds).")");
        $playerMonsters = [];
        foreach($dbResults as $dbResult) {
            $playerMonsters[intval($dbResult['player_id'])] = intval($dbResult['player_monster']) % 100;
        }
        $playersWithPotentialEvolution = array_values(array_filter($playersIds, fn($otherPlayer) => in_array($playerMonsters[$otherPlayer], $stepCardsMonsters)));

        if ($this->canPlayStepEvolution([$playerId], $this->EVOLUTION_TO_PLAY_BEFORE_END_ACTIVE) && !in_array($playerId, $playersWithPotentialEvolution)) {
            $playersWithPotentialEvolution[] = $playerId;
        }


        if (count($playersWithPotentialEvolution) == 0) {
            $this->goToState($this->redirectAfterBeforeEndTurn());
        } else {
            $this->gamestate->setPlayersMultiactive($playersWithPotentialEvolution, 'next', true);
        }
    }

    function stAfterBeforeEndTurn() {
        $this->goToState($this->redirectAfterBeforeEndTurn());
    }
    
}
