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

        if ($intervention === null) {
            //throw new \Exception('No damage informations found');
            return;
        }

        // if there is no more player to handle, end this state
        if (count($intervention->remainingPlayersId) == 0) {
            if ($this->isPowerUpExpansion()) {
                $this->goToState(ST_MULTIPLAYER_AFTER_RESOLVE_DAMAGE);
                return;
            }

            $this->deleteGlobalVariable(CANCEL_DAMAGE_INTERVENTION.$this->getStackedStateSuffix());
            $this->goToState($intervention->endState);
            return;
        }

        $currentPlayerId = $intervention->remainingPlayersId[0];

        // if current player is already eliminated, we ignore it
        if ($this->getPlayer($currentPlayerId)->eliminated) {
            array_shift($intervention->remainingPlayersId);
            $this->setGlobalVariable(CANCEL_DAMAGE_INTERVENTION.$this->getStackedStateSuffix(), $intervention);
            //$this->stCancelDamage();
            $this->goToState(ST_MULTIPLAYER_CANCEL_DAMAGE); // To update args, we can't call stCancelDamage directly
            return;
        }

        $currentDamage = $currentPlayerId !== null ? 
            $this->array_find($intervention->damages, fn($damage) => $damage->playerId == $currentPlayerId) : null;

        // if player will block damage, or he can not block damage anymore, we apply damage and remove it from remainingPlayersId
        if ($currentDamage 
            && ($this->canLoseHealth($currentPlayerId, $currentDamage->remainingDamage ?? $currentDamage->damage /*TODOWI remove after ??*/) !== null
                || !CancelDamageIntervention::canDoIntervention($this, $currentPlayerId, $currentDamage->remainingDamage ?? $currentDamage->damage /*TODOWI remove after ??*/, $currentDamage->damageDealerId, $currentDamage->clawDamage))
        ) {
            $this->applySkipCancelDamage($currentPlayerId);
            //$this->stCancelDamage();
            $this->goToState(ST_MULTIPLAYER_CANCEL_DAMAGE); // To update args, we can't call stCancelDamage directly
            return;
        }

        // if we are still here, player have cards to cancel/reduce damage. We check if he have enough energy to use them
        if ($this->autoSkipImpossibleActions()) {
            $arg = $this->argCancelDamage($currentPlayerId);
            if (!$arg['canDoAction']) {
                $this->applySkipCancelDamage($currentPlayerId);
                //$this->stCancelDamage();
                $this->goToState(ST_MULTIPLAYER_CANCEL_DAMAGE); // To update args, we can't call stCancelDamage directly
                return;
            }
        }

        // if we are still here, no action has been done automatically, we activate the player so he can heal
        $this->setDamageIntervention($intervention);
        $this->gamestate->setPlayersMultiactive([$currentPlayerId], 'stay', true);
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
