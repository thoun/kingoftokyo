<?php

namespace KOT\States;

trait EvolutionCardsActionTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */  

    function skipBeforeStartTurn() {
        $this->checkAction('skipBeforeStartTurn');

        $playerId = $this->getActivePlayerId();

        $this->goToState($this->redirectAfterBeforeStartTurn($playerId));
    }

    function applyChooseEvolutionCard(int $playerId, int $id) {
        $topCards = $this->pickEvolutionCards($playerId);
        $card = $this->array_find($topCards, fn($topCard) => $topCard->id == $id);
        if ($card == null) {
            throw new \BgaUserException('Evolution card not available');
        }
        $otherCard = $this->array_find($topCards, fn($topCard) => $topCard->id != $id);

        $this->evolutionCards->moveCard($id, 'hand', $playerId);
        $this->evolutionCards->moveCard($otherCard->id, 'dicard'.$playerId);

        $this->notifNewEvolutionCard($playerId, $card);
        
    } 

    function chooseEvolutionCard(int $id) {
        $this->checkAction('chooseEvolutionCard');

        $playerId = $this->getActivePlayerId();

        $this->applyChooseEvolutionCard($playerId, $id);

        $nextState = intval($this->getGameStateValue(STATE_AFTER_RESOLVE));
        $this->gamestate->jumpToState($nextState);
    }

    function playEvolution(int $id) {
        $playerId = $this->getCurrentPlayerId();

        $card = $this->getEvolutionCardFromDb($this->evolutionCards->getCard($id));

        if (!$this->canPlayEvolution($card->type, $playerId)) {
            throw new \BgaUserException("You can't play this Evolution.");
        }

        $countMothershipSupportBefore = $this->hasEvolutionOfType($playerId, MOTHERSHIP_SUPPORT_EVOLUTION) ? 1 : 0;

        $this->evolutionCards->moveCard($card->id, 'table', $playerId);
        
        $damages = $this->applyEvolutionEffects($card, $playerId);

        $this->playEvolutionToTable($playerId, $card);
        
        if (in_array($card->type, $this->AUTO_DISCARDED_EVOLUTIONS)) {
            $this->removeEvolution($playerId, $card, false, 5000);
        }
        
        $this->toggleMothershipSupport($playerId, $countMothershipSupportBefore);

        // TODOPU handle damages
    }

    function useYinYang() {
        $this->checkAction('useYinYang');

        $playerId = $this->getActivePlayerId();

        $hasYinYang = $this->isPowerUpExpansion() && $this->hasEvolutionOfType($playerId, YIN_YANG_EVOLUTION);
        if (!$hasYinYang) {
            throw new \BgaUserException("You can't play Yin & Yang without this Evolution.");
        }

        $this->applyYinYang($playerId);

        $this->gamestate->nextState('changeDie');
    }
    
    function useDetachableTail() {
        $this->checkAction('useDetachableTail');

        $playerId = $this->getCurrentPlayerId();

        if (!$this->hasEvolutionOfType($playerId, DETACHABLE_TAIL_EVOLUTION, false, true)) {
            throw new \BgaUserException('No Detachable Tail Evolution');
        }

        if ($this->canLoseHealth($playerId, 999) != null) {
            throw new \BgaUserException('You already invincible');
        }

        $this->removePlayerFromSmashedPlayersInTokyo($playerId);

        $card = $this->getEvolutionOfType($playerId, DETACHABLE_TAIL_EVOLUTION, true, true);

        $this->evolutionCards->moveCard($card->id, 'table', $playerId);

        $this->playEvolutionToTable($playerId, $card, clienttranslate('${player_name} uses ${card_name} to not lose [Heart] this turn'));

        $intervention = $this->getGlobalVariable(CANCEL_DAMAGE_INTERVENTION);
        $this->setInterventionNextState(CANCEL_DAMAGE_INTERVENTION, 'next', null, $intervention);
        $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');
    }
  	
    function putEnergyOnBambooSupply() {
        $this->checkAction('putEnergyOnBambooSupply');

        $playerId = $this->getCurrentPlayerId();

        $unusedBambooSupplyCard = $this->getFirstUnusedBambooSupply($playerId);

        $this->setEvolutionTokens($playerId, $unusedBambooSupplyCard, $unusedBambooSupplyCard->tokens + 1);

        // TODOPU

        $this->setUsedCard(3000 + $unusedBambooSupplyCard->id);

        $this->goToState($this->redirectAfterBeforeStartTurn($playerId));
    }
  	
    function takeEnergyOnBambooSupply() {
        $this->checkAction('takeEnergyOnBambooSupply');

        $playerId = $this->getCurrentPlayerId();

        $unusedBambooSupplyCard = $this->getFirstUnusedBambooSupply($playerId);

        $this->applyGetEnergyIgnoreCards($playerId, $unusedBambooSupplyCard->tokens, 3000 + BAMBOO_SUPPLY_EVOLUTION);
        $this->setEvolutionTokens($playerId, $unusedBambooSupplyCard, 0);

        $this->setUsedCard(3000 + $unusedBambooSupplyCard->id);

        $this->goToState($this->redirectAfterBeforeStartTurn($playerId));
    }
}
