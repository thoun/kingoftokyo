<?php

namespace KOT\States;

require_once(__DIR__.'/objects/card.php');
require_once(__DIR__.'/objects/player-intervention.php');
require_once(__DIR__.'/objects/damage.php');

use KOT\Objects\Card;
use KOT\Objects\OpportunistIntervention;
use KOT\Objects\Damage;
use KOT\Objects\PlayersUsedDice;

trait CardsStateTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stChooseMimickedCard() {
        if ($this->autoSkipImpossibleActions() && !$this->argChooseMimickedCard(true)['canChange']) {
            // skip state
            $this->skipChangeMimickedCard(true);
        }
    }

    function stBuyCard() {
        $this->deleteGlobalVariable(OPPORTUNIST_INTERVENTION);

        $playerId = intval(self::getActivePlayerId()); 

        // if player is dead async, he can't buy or sell
        if ($this->getPlayer($playerId)->eliminated) {
            $this->endTurn(true);
            return;
        }

        $args = $this->argBuyCard();
        if (boolval(self::getGameStateValue(SKIP_BUY_PHASE)) || ($this->autoSkipImpossibleActions() && !$args['canBuyOrNenew']) || $this->isSureWin($playerId)) {
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
        $playerId = self::getActivePlayerId();

        // metamorph
        $countMetamorph = $this->countCardOfType($playerId, METAMORPH_CARD);

        if ($countMetamorph < 1) { // no need to check remaining cards, if player got metamoph he got cards to sell
            $this->gamestate->nextState('endTurn');
        }
    }

    function stCancelDamage() {
        $this->stIntervention(CANCEL_DAMAGE_INTERVENTION);

        if ($this->autoSkipImpossibleActions()) {
            
            $intervention = $this->getGlobalVariable(CANCEL_DAMAGE_INTERVENTION);
            
            $playerId = null;
            if ($intervention != null && $intervention->remainingPlayersId != null && count($intervention->remainingPlayersId) > 0) {
                $playerId = $intervention->remainingPlayersId[0];
            } else {
                return;
            }

            $playersUsedDice = property_exists($intervention->playersUsedDice, $playerId) ? $intervention->playersUsedDice->{$playerId} : null;
            $dice = $playersUsedDice != null ? $playersUsedDice->dice : null;

            $hasBackgroundDweller = $this->countCardOfType($playerId, BACKGROUND_DWELLER_CARD) > 0; // Background Dweller

            $hasDice3 = $hasBackgroundDweller && $dice != null ? (array_search(3, $diceValues) !== false) : false;

            $arg = $this->argCancelDamage($playerId, $hasDice3);

            $canCancelWithCamouflage = $arg['canThrowDices'] || $arg['rethrow3']['hasDice3'];
            $canCancelWithWings = $arg['canUseWings'] && $arg['playerEnergy'] >= 2;
            $canCancelWithRobot = $arg['canUseRobot'] && $arg['playerEnergy'] >= 1;
            if (!$canCancelWithCamouflage && !$canCancelWithWings && !$canCancelWithRobot) {
                $this->applySkipWings($playerId);
            }
        }
    }

    function stStealCostumeCard() {
        $playerId = self::getActivePlayerId();

        $diceCounts = $this->getGlobalVariable(DICE_COUNTS, true);

        if ($diceCounts[6] < 3) {
            // skip state, can't steal cards (not enough smashes)
            $this->gamestate->nextState('endStealCostume');
            return;
        }
        
        $args = $this->argStealCostumeCard();
        if ($this->autoSkipImpossibleActions() && !$args['canBuyFromPlayers']) {
            // skip state, can't buy cards
            $this->gamestate->nextState('endStealCostume');
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

                if ($playerId != intval(self::getActivePlayerId())) {
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
}
