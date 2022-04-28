<?php

namespace KOT\States;

require_once(__DIR__.'/../objects/player-intervention.php');

use KOT\Objects\CancelDamageIntervention;

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
        $intervention = $this->getDamageIntervention();
        $currentPlayerId = $intervention != null && $intervention->remainingPlayersId != null && count($intervention->remainingPlayersId) > 0 ?
            $intervention->remainingPlayersId[0] : null;
        $currentDamage = $currentPlayerId !== null ? 
            $this->array_find($intervention->damages, fn($damage) => $damage->playerId == $currentPlayerId) : null;

        if ($currentDamage 
            && ($this->canLoseHealth($currentPlayerId, $currentDamage->remainingDamage ?? $currentDamage->damage /*TODOWI remove after ??*/) !== null
                || !CancelDamageIntervention::canDoIntervention($this, $currentPlayerId, $currentDamage->remainingDamage ?? $currentDamage->damage /*TODOWI remove after ??*/, $currentDamage->damageDealerId))
        ) {
            $this->applySkipCancelDamage($currentPlayerId);
        }

        // TODOBUG TODOPU old stIntervention;

        $keep = ($intervention->nextState === 'keep' || $intervention->nextState === 'next') 
            && count($intervention->remainingPlayersId) > 0
            && !$this->getPlayer($intervention->remainingPlayersId[0])->eliminated;
            
        if ($keep) {
            // we check if player still ca do intervention (in case player got mimic, and mimicked camouflage player dies before mimic player intervention)

            $playerId = $intervention->remainingPlayersId[0];

            $damageDealerId = 0;
            $damage = 0;
            foreach($intervention->damages as $d) {
                if ($d->playerId == $playerId) {
                    $damage = $d->damage;
                    $damageDealerId = $d->damageDealerId;
                    break;
                }
            } 

            $keep = CancelDamageIntervention::canDoIntervention($this, $playerId, $damage, $damageDealerId);

            // if player cannot cancel damage, we apply them
            if (!$keep) {           
                foreach($intervention->damages as $d) {
                    if ($d->playerId == $playerId) {
                        $this->applyDamage($d->playerId, $d->damage, $d->damageDealerId, $d->cardType, $this->getActivePlayerId(), $d->giveShrinkRayToken, $d->givePoisonSpitToken, $d->smasherPoints);
                    }
                } 
            }
        }
        
        if ($keep) { // current player continues / next (intervention player) / or leaving transition
            $this->gamestate->setPlayersMultiactive([$intervention->remainingPlayersId[0]], 'transitionError', true);
        } else { // leaving transition
            if ($this->isPowerUpExpansion()) {
                $this->goToState(ST_MULTIPLAYER_AFTER_RESOLVE_DAMAGE);
                return;
            }

            $this->deleteGlobalVariable(CANCEL_DAMAGE_INTERVENTION.$this->getStackedStateSuffix());
            $this->goToState($intervention->endState);
        }
        // TODOBUG TODOPU end old stIntervention

        $intervention = $this->getDamageIntervention();
        if ($intervention !== null && $this->autoSkipImpossibleActions()) {
            
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

            $arg = $this->argCancelDamage($playerId, $hasDice3, $intervention);

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
                $this->applySkipCancelDamage($playerId);
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
