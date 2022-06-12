<?php

namespace KOT\States;

trait RedirectionTrait {
    function goToState($nextStateId, /*Damage[] | null*/ $damages = null) {
        if ($nextStateId === null) {
            $nextStateId = intval($this->gamestate->state_id());
        }

        if ($damages != null && count($damages) > 0) {
            $this->resolveDamages($damages, $nextStateId);
        } else if ($nextStateId == -1) {
            $this->removeStackedStateAndRedirect();
        } else {
            $this->jumpToState($nextStateId);
        }
    }

    function redirectAfterStart() {
        if ($this->canPickMonster()) {
            return ST_PLAYER_PICK_MONSTER; 
        } else {
            return $this->redirectAfterPickMonster();
        }
    }

    function redirectAfterPickMonster() {
        if ($this->isPowerUpMutantEvolution()) {
            return ST_NEXT_PICK_EVOLUTION_DECK; 
        } else {
            return $this->redirectAfterPickEvolutionDeck();
        }
    }

    function redirectAfterPickEvolutionDeck() {
        if ($this->isHalloweenExpansion() || $this->isPowerUpExpansion()) {
            return ST_PLAYER_CHOOSE_INITIAL_CARD; 
        } else {
            return $this->redirectAfterChooseInitialCard();
        }
    }

    function redirectAfterChooseInitialCard() {
        return ST_START_GAME; 
    }

    function redirectAfterBeforeStartTurn() {
        if ($this->isPowerUpExpansion()) {
            return ST_QUESTIONS_BEFORE_START_TURN;
        } else {
            return ST_START_TURN;
        }
    }

    function redirectAfterStartTurn(int $playerId) {
        if ($this->canChangeMimickedCardWickednessTile($playerId)) {
            return ST_PLAYER_CHANGE_MIMICKED_CARD_WICKEDNESS_TILE;
        }
        return $this->redirectAfterChangeMimickWickednessTile($playerId);
    }

    function redirectAfterChangeMimickWickednessTile(int $playerId) {
        if ($this->canChangeMimickedCard($playerId)) {
            return ST_PLAYER_CHANGE_MIMICKED_CARD;
        }
        return $this->redirectAfterChangeMimick($playerId);
    }

    function redirectAfterChangeMimick(int $playerId) {
        $playerIdWithGoldenScarab = $this->getPlayerIdWithGoldenScarab();
        if ($this->isAnubisExpansion() && $this->getCurseCardType() == KHEPRI_S_REBELLION_CURSE_CARD && $playerIdWithGoldenScarab != null && $playerId != $playerIdWithGoldenScarab) {
            return ST_MULTIPLAYER_GIVE_SYMBOL_TO_ACTIVE_PLAYER;
        }
        return ST_INITIAL_DICE_ROLL;
    }

    function redirectAfterResolveDice() {
        if ($this->isPowerUpExpansion()) {
            return ST_PLAYER_BEFORE_RESOLVE_DICE;
        } else {
            return ST_RESOLVE_NUMBER_DICE;
        }
    }

    function redirectAfterBeforeResolveDice() {
        return ST_RESOLVE_NUMBER_DICE;
    }

    function redirectAfterResolveNumberDice() {
        $playerId = $this->getActivePlayerId();

        if ($this->isWickednessExpansion() && $this->canTakeWickednessTile($playerId) > 0) {
            return ST_PLAYER_TAKE_WICKEDNESS_TILE;
        }

        $diceCounts = $this->getGlobalVariable(DICE_COUNTS, true);

        $canSelectHeartDiceUse = false;
        if ($diceCounts[4] > 0) {
            $selectHeartDiceUse = $this->getSelectHeartDiceUse($playerId);
            $canHealWithDice = $this->canHealWithDice($playerId);

            $canRemoveToken = ($selectHeartDiceUse['shrinkRayTokens'] > 0 || $selectHeartDiceUse['poisonTokens'] > 0) && $canHealWithDice;

            $canSelectHeartDiceUse = ($selectHeartDiceUse['hasHealingRay'] && count($selectHeartDiceUse['healablePlayers']) > 0) || $canRemoveToken;
        }

        if ($canSelectHeartDiceUse) {
            return ST_RESOLVE_HEART_DICE_ACTION; 
        } else {
            return ST_RESOLVE_HEART_DICE;
        }
    }

    function redirectAfterResolveEnergyDice() {
        $playerId = $this->getActivePlayerId();
        $diceCounts = $this->getGlobalVariable(DICE_COUNTS, true);

        $canUsePlayWithYourFood = $this->canUsePlayWithYourFood($playerId, $diceCounts);

        if ($canUsePlayWithYourFood !== null) {
            return ST_RESOLVE_SMASH_DICE_ACTION; 
        } else {
            return ST_RESOLVE_SMASH_DICE;
        }
    }

    function redirectAfterHalfMovePhase() {
        return ST_ENTER_TOKYO;
    }

    function redirectAfterPrepareResolveDice() {
        if ($this->isHalloweenExpansion()) {
            return ST_MULTIPLAYER_CHEERLEADER_SUPPORT;
        } else {
            return ST_RESOLVE_DIE_OF_FATE;
        }
    }

    function redirectAfterEnterTokyo(int $playerId) {
        if ($this->isHalloweenExpansion()) { 
            return ST_PLAYER_STEAL_COSTUME_CARD;
        } else {
            return $this->redirectAfterStealCostume($playerId);
        }
    }

    function redirectAfterStealCostume(int $playerId) {
        if ($this->isMutantEvolutionVariant() && $this->getFormCard($playerId) != null) { 
            return ST_PLAYER_CHANGE_FORM;
        } else {
            return ST_PLAYER_BUY_CARD;
        }
    }
    
}
