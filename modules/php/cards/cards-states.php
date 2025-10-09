<?php

namespace KOT\States;

require_once(__DIR__.'/../Objects/player-intervention.php');

use KOT\Objects\CancelDamageIntervention;

trait CardsStateTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stBuyCard() {
        $this->deleteGlobalVariable(OPPORTUNIST_INTERVENTION);

        $playerId = intval($this->getActivePlayerId()); 

        // if player is dead async, he can't buy or sell
        if ($this->getPlayer($playerId)->eliminated) {
            return $this->actEndTurn();
        }

        $args = $this->argBuyCard();
        if ($this->countCardOfType($playerId, HIBERNATION_CARD) > 0 || boolval($this->getGameStateValue(SKIP_BUY_PHASE)) || ($this->autoSkipImpossibleActions() && !$args['canBuyOrNenew']) || $this->isSureWin($playerId) || ($this->isMutantEvolutionVariant() && $this->isBeastForm($playerId))) {
            // skip state
            if ($args['canSell']) {
                return $this->actGoToSellCard();
            } else {
            return $this->actEndTurn();
            }
        }
    }
    function stOpportunistChooseMimicCard() {
        $intervention = $this->getGlobalVariable(OPPORTUNIST_INTERVENTION);

        $this->gamestate->setPlayersMultiactive([$intervention->remainingPlayersId[0]], 'stay', true);
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
