<?php

namespace KOT\States;

trait CardsStateTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stChooseMimickedCard() {
        if (($this->autoSkipImpossibleActions() && !$this->argChooseMimickedCard(true)['canChange']) || $this->getPlayer($this->getActivePlayerId())->eliminated) {
            // skip state
            $this->skipChangeMimickedCard(true);
        }
    }

    function stBuyCard() {
        $this->deleteGlobalVariable(OPPORTUNIST_INTERVENTION);

        $playerId = intval($this->getActivePlayerId()); 

        // if player is dead async, he can't buy or sell
        if ($this->getPlayer($playerId)->eliminated) {
            $this->endTurn(true);
            return;
        }

        $args = $this->argBuyCard();
        if ($this->countCardOfType($playerId, HIBERNATION_CARD) > 0 || boolval($this->getGameStateValue(SKIP_BUY_PHASE)) || ($this->autoSkipImpossibleActions() && !$args['canBuyOrNenew']) || $this->isSureWin($playerId) || ($this->isMutantEvolutionVariant() && $this->isBeastForm($playerId))) {
            // skip state
            if ($args['canSell']) {
                $this->goToSellCard(true);
            } else {
                $this->endTurn(true);
            }
        }
    }

    function stOpportunistBuyCard() {
        if ($this->autoSkipImpossibleActions()) { // in turn based, we remove players when they can't buy anything
            $intervention = $this->getGlobalVariable(OPPORTUNIST_INTERVENTION);
            $remainingPlayersId = [];
            foreach($intervention->remainingPlayersId as $playerId) {
                if ($this->argOpportunistBuyCardWithPlayerId($playerId)['canBuy']) {
                    $remainingPlayersId[] = $playerId;
                } else {
                    $this->removeDiscardCards($playerId);
                }
            }
            $intervention->remainingPlayersId = $remainingPlayersId;
            $this->setGlobalVariable(OPPORTUNIST_INTERVENTION, $intervention);
        }

        $this->stIntervention(OPPORTUNIST_INTERVENTION);
    }

    function stOpportunistChooseMimicCard() {
        $intervention = $this->getGlobalVariable(OPPORTUNIST_INTERVENTION);

        $this->gamestate->setPlayersMultiactive([$intervention->remainingPlayersId[0]], 'stay', true);
    }

    function stSellCard() {
        $playerId = $this->getActivePlayerId();

        // metamorph
        $countMetamorph = $this->countCardOfType($playerId, METAMORPH_CARD);

        if ($countMetamorph < 1) { // no need to check remaining cards, if player got metamoph he got cards to sell
            $this->gamestate->nextState('endTurn');
        }
    }

    function stCancelDamage() {
        $this->stIntervention(CANCEL_DAMAGE_INTERVENTION.$this->getStackedStateSuffix());

        if ($this->autoSkipImpossibleActions()) {
            
            $intervention = $this->getDamageIntervention();
            
            $playerId = null;
            if ($intervention != null && $intervention->remainingPlayersId != null && count($intervention->remainingPlayersId) > 0) {
                $playerId = $intervention->remainingPlayersId[0];
            } else {
                return;
            }

            $playersUsedDice = property_exists($intervention->playersUsedDice, $playerId) ? $intervention->playersUsedDice->{$playerId} : null;
            $dice = $playersUsedDice != null ? $playersUsedDice->dice : null;
            $diceValues = $dice != null ? array_map(fn($die) => $die->value, $dice) : [];

            $hasBackgroundDweller = $this->countCardOfType($playerId, BACKGROUND_DWELLER_CARD) > 0; // Background Dweller

            $hasDice3 = $hasBackgroundDweller && $dice != null ? in_array(3, $diceValues) : false;

            $arg = $this->argCancelDamage($playerId, $hasDice3);

            $canCancelWithCamouflage = $arg['canThrowDices'] || $arg['rethrow3']['hasDice3'];

            $potentialEnergy = $this->getPlayerEnergy($playerId);
            if ($this->isCthulhuExpansion()) {
                $potentialEnergy += $this->getPlayerCultists($playerId);
            }

            $canCancelWithWings = $arg['canUseWings'] && $potentialEnergy >= 2;
            $canCancelWithDetachableTail = $arg['canUseDetachableTail'];
            $canUseRabbitsFoot = $arg['canUseRabbitsFoot'];
            $canCancelWithRobot = $arg['canUseRobot'] && $potentialEnergy >= 1;
            $canCancelWithSuperJump = $arg['superJumpHearts'] > 0 && $potentialEnergy >= 1;
            $canCancelWithRapidHealing = $arg['damageToCancelToSurvive'] && $arg['damageToCancelToSurvive'] >= 1;
            if (!$canCancelWithCamouflage && !$canCancelWithWings && !$canCancelWithRobot && !$canCancelWithRapidHealing && !$canCancelWithSuperJump && !$canCancelWithDetachableTail && !$canUseRabbitsFoot) {
                $this->applySkipWings($playerId);
            }
        }
    }

    function stStealCostumeCard() {
        $diceCounts = $this->getGlobalVariable(DICE_COUNTS, true);

        $playerId = $this->getActivePlayerId();

        if ($diceCounts[6] < 3 || $this->getPlayer($playerId)->eliminated) {
            // skip state, can't steal cards (not enough smashes) or dead
            $this->goToState($this->redirectAfterStealCostume($playerId));
            return;
        }
        
        $args = $this->argStealCostumeCard();
        if ($this->autoSkipImpossibleActions() && !$args['canBuyFromPlayers']) {
            // skip state, can't buy cards
            $this->goToState($this->redirectAfterStealCostume($playerId));
            return;
        }
    }

    function stCheerleaderSupport() {
        $cheerleaderSupportPlayerIds = [];
        $cheerleaderCards = $this->getCardsFromDb($this->cards->getCardsOfType(CHEERLEADER_CARD));
        if (count($cheerleaderCards) > 0) {
            $cheerleaderCard = $cheerleaderCards[0];
        
            if ( $cheerleaderCard->location == 'hand') {
                $playerId = intval($cheerleaderCard->location_arg);

                if ($playerId != intval($this->getActivePlayerId())) {
                    $cheerleaderSupportPlayerIds[] = $playerId;
                }
            }
        }

        if (count($cheerleaderSupportPlayerIds) > 0) {
            $this->gamestate->setPlayersMultiactive($cheerleaderSupportPlayerIds, 'end', true);
        } else {
            $this->gamestate->nextState('end');
        }
    }

    function stLeaveTokyoExchangeCard() {
        $args = $this->argLeaveTokyoExchangeCard();
        if ($this->autoSkipImpossibleActions() && !$args['canExchange']) {
            // skip state, can't exchange card
            $this->gamestate->nextState('next');
            return;
        }

        $leaversWithUnstableDNA = $this->getLeaversWithUnstableDNA(); 
        
        $this->gamestate->setPlayersMultiactive($leaversWithUnstableDNA, 'transitionError', true);
    }
}
