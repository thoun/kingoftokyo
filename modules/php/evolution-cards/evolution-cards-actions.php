<?php

namespace KOT\States;

require_once(__DIR__.'/../objects/evolution-card.php');

use KOT\Objects\EvolutionCard;

trait EvolutionCardsActionTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */  

    function pickEvolutionForDeck(int $id) {
        $this->checkAction('pickEvolutionForDeck');

        $playerId = $this->getCurrentPlayerId();

        $card = $this->getEvolutionCardFromDb($this->evolutionCards->getCard($id));

        if (strpos($card->location, 'mutant') !== 0) {
            throw new \BgaUserException("Card is not selectable");
        }

        $this->evolutionCards->moveCard($id, 'deck'.$playerId);

        $this->notifyPlayer($playerId, 'evolutionPickedForDeck', '', [
            'card' => $card,
        ]);

        $this->gamestate->setPlayerNonMultiactive($playerId, 'next');
    }

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

        $this->notifNewEvolutionCard($playerId, $card, /*client TODOPU translate(*/'${player_name} ends his rolls with at least 3 [diceHeart] and takes a new Evolution card'/*)*/);
        
    } 

    function chooseEvolutionCard(int $id) {
        $this->checkAction('chooseEvolutionCard');

        $playerId = $this->getActivePlayerId();

        $this->applyChooseEvolutionCard($playerId, $id);

        $nextState = intval($this->getGameStateValue(STATE_AFTER_RESOLVE));
        $this->gamestate->jumpToState($nextState);
    }

    function applyPlayEvolution(int $playerId, EvolutionCard $card) {
        $countMothershipSupportBefore = $this->countEvolutionOfType($playerId, MOTHERSHIP_SUPPORT_EVOLUTION);

        $this->evolutionCards->moveCard($card->id, 'table', $playerId);
        
        $damages = $this->applyEvolutionEffects($card, $playerId);

        $this->playEvolutionToTable($playerId, $card);
        
        if (in_array($card->type, $this->AUTO_DISCARDED_EVOLUTIONS)) {
            $this->removeEvolution($playerId, $card, false, 5000);
        }
        
        $this->toggleMothershipSupport($playerId, $countMothershipSupportBefore);

        // TODOPU handle damages
    }

    function playEvolution(int $id) {
        $playerId = $this->getCurrentPlayerId();

        $card = $this->getEvolutionCardFromDb($this->evolutionCards->getCard($id));

        if (!$this->canPlayEvolution($card->type, $playerId)) {
            throw new \BgaUserException("You can't play this Evolution.");
        }

        $this->applyPlayEvolution($playerId, $card);
    }

    function useYinYang() {
        $this->checkAction('useYinYang');

        $playerId = $this->getActivePlayerId();

        $hasYinYang = $this->isPowerUpExpansion() && $this->countEvolutionOfType($playerId, YIN_YANG_EVOLUTION) > 0;
        if (!$hasYinYang) {
            throw new \BgaUserException("You can't play Yin & Yang without this Evolution.");
        }

        $this->applyYinYang($playerId);

        $this->gamestate->nextState('changeDie');
    }
    
    function useDetachableTail() {
        $this->checkAction('useDetachableTail');

        $playerId = $this->getCurrentPlayerId();

        if ($this->countEvolutionOfType($playerId, DETACHABLE_TAIL_EVOLUTION, false, true) == 0) {
            throw new \BgaUserException('No Detachable Tail Evolution');
        }

        if ($this->canLoseHealth($playerId, 999) != null) {
            throw new \BgaUserException('You already invincible');
        }

        $this->removePlayerFromSmashedPlayersInTokyo($playerId);

        $card = $this->getEvolutionsOfType($playerId, DETACHABLE_TAIL_EVOLUTION, true, true)[0];

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

    function skipCardIsBought() {
        $this->checkAction('skipCardIsBought');

        $playerId = $this->getCurrentPlayerId();

        // Make this player unactive now (and tell the machine state to use transtion "resume" if all players are now unactive
        $this->gamestate->setPlayerNonMultiactive($playerId, 'next');
    }

    function buyCardBamboozle(int $id, int $from) {
        $currentPlayerId = $this->getCurrentPlayerId();
        $activePlayerId = $this->getActivePlayerId();

        $forcedCard = $this->getCardFromDb($this->cards->getCard($id));
        $forbiddenCard = $this->getCardFromDb($this->cards->getCard($this->getGlobalVariable(CARD_BEING_BOUGHT)->cardId));

        $this->notifyAllPlayers('log', /*client TODOPU translate(*/'${player_name} force ${player_name2} to buy ${card_name} instead of ${card_name2}. ${player_name2} cannot buy ${card_name2} this turn'/*)*/, [
            'player_name' => $this->getPlayerName($currentPlayerId),
            'player_name2' => $this->getPlayerName($activePlayerId),
            'card_name' => $forcedCard->type,
            'card_name2' => $forbiddenCard->type,
        ]);

        $this->applyBuyCard($activePlayerId, $id, $from, false);

        $this->goToState(ST_PLAYER_BUY_CARD);
    }
}
