<?php

namespace KOT\States;

require_once(__DIR__.'/../Objects/question.php');

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

        $worstNightmareEvolution = $this->getGiftEvolutionOfType($playerId, WORST_NIGHTMARE_EVOLUTION);
        if ($worstNightmareEvolution != null && !in_array(3000 + $worstNightmareEvolution->id, $this->getUsedCard())) {
            $applied = $this->applyWorstNightmare($playerId, $worstNightmareEvolution);
            if ($applied) {
                return;
            } else {
                $this->setUsedCard(3000 + $worstNightmareEvolution->id);
            }
        }

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
            if ($playerId != $ownerId && !$this->getPlayer($ownerId)->eliminated) {
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
            } else {
                $this->setUsedCard(3000 + $unsetFreezeRayCard->id);
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
            } else {
                $this->setEvolutionTokens($playerId, $unusedEnergySwordCard, 0, true);
            }
        }

        // must be the last question because it ends the turn
        $unusedTempleEvolutionCard = $this->getFirstUnusedEvolution($playerId, SUNKEN_TEMPLE_EVOLUTION);
        if ($unusedTempleEvolutionCard != null && !$this->inTokyo($playerId)) {

            $question = new Question(
                'SunkenTemple',
                clienttranslate('${actplayer} can pass turn to gain 3[Heart] and 3[Energy]'),
                clienttranslate('${you} can pass your turn to gain 3[Heart] and 3[Energy]'),
                [$playerId],
                ST_QUESTIONS_BEFORE_START_TURN,
                []
            );
            $this->setQuestion($question);
            $this->gamestate->setPlayersMultiactive([$playerId], 'next', true);

            $this->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
            return;
        }
        $this->goToState(ST_START_TURN);
    }

    function stCardIsBought() {
        $otherPlayersIds = $this->getOtherPlayersIds($this->getActivePlayerId());
        $playersWithPotentialEvolution = $this->getPlayersIdsWhoCouldPlayEvolutions($otherPlayersIds, $this->EVOLUTION_TO_PLAY_WHEN_CARD_IS_BOUGHT);

        $this->gamestate->setPlayersMultiactive($playersWithPotentialEvolution, 'next', true);
    }

    function stAfterCardIsBought() {
        $cardBeingBought = $this->getGlobalVariable(CARD_BEING_BOUGHT);

        if ($cardBeingBought->allowed) {
            // applyBuyCard do the redirection
            $this->applyBuyCard($cardBeingBought->playerId, $cardBeingBought->cardId, $cardBeingBought->from, $cardBeingBought->cost, $cardBeingBought->useSuperiorAlienTechnology, $cardBeingBought->useBobbingForApples);
        } else {
            $this->goToState(ST_PLAYER_BUY_CARD);
        }
    }

    function stAnswerQuestion() {
        $activePlayers = $this->gamestate->getActivePlayerList();
        if (count($activePlayers) == 0) {
            $question = $this->getQuestion();
            $this->gamestate->setPlayersMultiactive($question->playersIds, 'next', true);
        }
    }

    function stAfterAnswerQuestion() {
        $question = $this->getQuestion();

        if ($question->code === 'GiveSymbol' || $question->code === 'GiveEnergyOrLoseHearts' || $question->code === 'ElectricCarrot') {
            if ($question->code === 'GiveSymbol' || $question->code === 'GiveEnergyOrLoseHearts') {
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
        $couldPlay = count($this->getPlayersIdsWhoCouldPlayEvolutions([$playerId], $this->EVOLUTION_TO_PLAY_BEFORE_RESOLVE_DICE)) > 0;

        if ($couldPlay) {
            $this->gamestate->setPlayersMultiactive([$playerId], 'next', true);
        } else {
            $this->goToState($this->redirectAfterBeforeResolveDice());
        }
    }

    function stAfterResolveDamage() {
        $intervention = $this->getDamageIntervention();

        $activePlayerId = $this->getActivePlayerId();
        $freezeRayEvolutions = $this->getEvolutionsOfType($activePlayerId, FREEZE_RAY_EVOLUTION);
        $freezeRayEvolution = $this->array_find($freezeRayEvolutions, fn($evolution) => $evolution->ownerId == $activePlayerId);
        if ($freezeRayEvolution != null) {
            $woundDamagesByFreezeRayOwner = array_values(array_filter($intervention->damages, fn($damage) => 
                $damage->clawDamage != null && $damage->damageDealerId == $activePlayerId && $damage->effectiveDamage > 0
            ));
            $woundedPlayersByFreezeRayOwner = array_values(array_unique(array_map(fn($damage) => $damage->playerId, $woundDamagesByFreezeRayOwner)));
            $woundedPlayersByFreezeRayOwner = array_values(array_filter($woundedPlayersByFreezeRayOwner, fn($playerId) => $this->inTokyo($playerId)));

            if (count($woundedPlayersByFreezeRayOwner) === 1) {
                $this->giveFreezeRay($activePlayerId, $woundedPlayersByFreezeRayOwner[0], $freezeRayEvolution);
            } else if (count($woundedPlayersByFreezeRayOwner) > 1) {
                $this->freezeRayChooseOpponentQuestion($activePlayerId, $woundedPlayersByFreezeRayOwner, $freezeRayEvolution);
                return;
            }
        }

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
        $playersWithPotentialEvolution = $this->getPlayersIdsWhoCouldPlayEvolutions($playersIds, $this->EVOLUTION_TO_PLAY_BEFORE_END_MULTI);

        $activePlayersWithPotentialEvolution = $this->getPlayersIdsWhoCouldPlayEvolutions([$playerId], $this->EVOLUTION_TO_PLAY_BEFORE_END_ACTIVE);
        $activePlayersWithPotentialEvolution = array_values(array_filter($activePlayersWithPotentialEvolution, fn($pId) => $pId == $playerId));
        if (count($activePlayersWithPotentialEvolution) > 0 && !in_array($playerId, $playersWithPotentialEvolution)) {
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
