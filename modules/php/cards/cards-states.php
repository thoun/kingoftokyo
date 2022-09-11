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
            $this->goToState($this->redirectAfterSellCard());
        }
    }

    function stCancelDamage() {            
        $intervention = $this->getDamageIntervention();

        if ($intervention === null) {
            throw new \Exception('No damage informations found');
            return;
        }

        //$this->resolveRemainingDamages($intervention, false, true);
        
        $this->gamestate->setPlayersMultiactive([$intervention->remainingPlayersId[0]], 'stay', true);
    }

    function stStealCostumeCard() {
        $playerId = $this->getActivePlayerId();

        if ($this->getPlayer($playerId)->eliminated) {
            // skip state, can't steal cards if dead
            $this->goToState($this->redirectAfterStealCostume($playerId));
            return;
        }
        
        $args = $this->argStealCostumeCard();
        if ($this->autoSkipImpossibleActions() && !$args['canBuyFromPlayers'] && !$args['canGiveGift']) {
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

                $activePlayerId = intval($this->getActivePlayerId());
                if ($playerId != $activePlayerId && $this->canUseSymbol($activePlayerId, 6) && $this->canUseFace($activePlayerId, 6)) {
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
