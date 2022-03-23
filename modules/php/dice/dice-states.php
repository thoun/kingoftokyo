<?php

namespace KOT\States;

require_once(__DIR__.'/../objects/damage.php');

use KOT\Objects\Damage;

function getDieFace($die) {
    if ($die->type === 2) {
        return 10;
    } else if ($die->type === 1) {
        if ($die->value <= 2) {
            return 5;
        } else if ($die->value <= 5) {
            return 6;
        } else {
            return 7;
        }
    } else {
        return $die->value;
    }
}

trait DiceStateTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stThrowDice() {
        // disabled so player can see last roll
        /*if ($this->autoSkipImpossibleActions() && !$this->argThrowDice()['hasActions']) {
            // skip state
            $this->goToChangeDie(true);
        }*/
    }

    function stChangeDie() {
        $playerId = $this->getActivePlayerId();

        $canChangeWithCards = $this->canChangeDie($this->getChangeDieCards($playerId));
        $canRetrow3 = intval($this->getGameStateValue(PSYCHIC_PROBE_ROLLED_A_3)) > 0 && $this->countCardOfType($playerId, BACKGROUND_DWELLER_CARD) > 0;
        
        if (!$canChangeWithCards && !$canRetrow3) {
            $this->gamestate->nextState('resolve');
        }
    }

    function stChangeActivePlayerDie() {
        $this->stIntervention(CHANGE_ACTIVE_PLAYER_DIE_INTERVENTION);
    }

    function stPrepareResolveDice() {
        if ($this->isHalloweenExpansion()) {
            $this->gamestate->nextState('askCheerleaderSupport');
        } else {
            $this->gamestate->nextState('resolve');
        }
    }

    function applyResolveDice(int $playerId) {
        $playerInTokyo = $this->inTokyo($playerId);
        $dice = $this->getPlayerRolledDice($playerId, true, true, false);
        usort($dice, "static::sortDieFunction");

        $diceStr = '';
        foreach($dice as $die) {
            $diceStr .= $this->getDieFaceLogName($die->value, $die->type);
        }

        $this->notifyAllPlayers("resolvePlayerDice", clienttranslate('${player_name} resolves dice ${dice}'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'dice' => $diceStr,
        ]);

        $diceCounts = $this->getRolledDiceCounts($playerId, $dice, false);

        $detail = $this->addSmashesFromCards($playerId, $diceCounts, $playerInTokyo);
        $diceCounts[6] += $detail->addedSmashes;

        if ($detail->addedSmashes > 0) {
            $diceStr = '';
            for ($i=0; $i<$detail->addedSmashes; $i++) { 
                $diceStr .= $this->getDieFaceLogName(6, 0); 
            }
            
            $cardNamesStr = implode(', ', $detail->cardsAddingSmashes);

            $this->notifyAllPlayers("resolvePlayerDiceAddedDice", clienttranslate('${player_name} adds ${dice} with ${card_name}'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'dice' => $diceStr,
                'card_name' => $cardNamesStr,
            ]);
        }

        // detritivore
        if ($diceCounts[1] >= 1 && $diceCounts[2] >= 1 && $diceCounts[3] >= 1) {
            $countDetritivore = $this->countCardOfType($playerId, DETRITIVORE_CARD);
            if ($countDetritivore > 0) {
                $this->applyGetPoints($playerId, 2 * $countDetritivore, DETRITIVORE_CARD);
            }

            // complete destruction
            $rolledFaces = $this->getRolledDiceFaces($playerId, $dice, false);
            if ($rolledFaces[41] >= 1 && $rolledFaces[51] >= 1 && $rolledFaces[61] >= 1) { // dice 1-2-3 check with previous if
                $countCompleteDestruction = $this->countCardOfType($playerId, COMPLETE_DESTRUCTION_CARD);
                if ($countCompleteDestruction > 0) {
                    $this->applyGetPoints($playerId, 9 * $countCompleteDestruction, COMPLETE_DESTRUCTION_CARD);
                }

                if ($this->isPowerUpExpansion()) {
                    if ($this->hasEvolutionOfType($playerId, PANDA_EXPRESS_EVOLUTION)) {
                        $this->applyGetPoints($playerId, 2, 3000 + PANDA_EXPRESS_EVOLUTION);
                        $this->setGameStateValue(PANDA_EXPRESS_EXTRA_TURN, 1);
                    }
                }
            }
        }

        $fireBreathingDamages = [];
        // fire breathing
        if ($diceCounts[6] >= 1) {
            $countFireBreathing = $this->countCardOfType($playerId, FIRE_BREATHING_CARD);
            if ($countFireBreathing > 0) {
                $playersIds = $this->getPlayersIds();
                $playerIndex = array_search($playerId, $playersIds);
                $playerCount = count($playersIds);
                
                $leftPlayerId = $playersIds[($playerIndex + 1) % $playerCount];
                $rightPlayerId = $playersIds[($playerIndex + $playerCount - 1) % $playerCount];

                if ($leftPlayerId != $playerId) {
                    $fireBreathingDamages[$leftPlayerId] = $countFireBreathing;
                }
                if ($rightPlayerId != $playerId && $rightPlayerId != $leftPlayerId) {
                    $fireBreathingDamages[$rightPlayerId] = $countFireBreathing;
                }
            }
        }

        if ($diceCounts[1] >= 4 && $this->isKingKongExpansion() && $this->inTokyo($playerId) && $this->canUseSymbol($playerId, 1) && $this->canUseFace($playerId, 1)) {
            $this->getNewTokyoTowerLevel($playerId);
        }
        
        if ($this->isCthulhuExpansion()) {
            for ($diceFace = 1; $diceFace <= 6; $diceFace++) {
                if ($diceCounts[$diceFace] >= 4 && $this->canUseSymbol($playerId, $diceFace) && $this->canUseFace($playerId, $diceFace)) {
                    $this->applyGetCultist($playerId, $diceFace);
                }
            }
        }
        
        if ($this->isPowerUpExpansion()) {
            if ($diceCounts[4] >= 3 && $this->hasEvolutionOfType($playerId, PANDARWINISM_EVOLUTION)) {
                $this->applyGetPoints($playerId, $diceCounts[4] - 2, 3000 + PANDARWINISM_EVOLUTION);
            }
        }

        $this->setGlobalVariable(FIRE_BREATHING_DAMAGES, $fireBreathingDamages);
        $this->setGlobalVariable(DICE_COUNTS, $diceCounts);
    }

    function stResolveDice() {
        $this->updateKillPlayersScoreAux();
        
        $playerId = $this->getActivePlayerId();
        $this->giveExtraTime($playerId);

        $this->DbQuery("UPDATE dice SET `locked` = true, `rolled` = false");

        $countHibernation = $this->countCardOfType($playerId, HIBERNATION_CARD);
        if ($countHibernation == 0) {
            $this->applyResolveDice($playerId);
            
            $this->goToState($this->redirectAfterResolveDice());
        }
    }

    function stResolveNumberDice() {
        $playerId = $this->getActivePlayerId();

        if ($this->countCardOfType($playerId, HIBERNATION_CARD) > 0) {
            $this->jumpToState($this->getRedirectAfterResolveNumberDice());
            return;
        }

        $diceCounts = $this->getGlobalVariable(DICE_COUNTS, true);

        for ($diceFace = 1; $diceFace <= 3; $diceFace++) {
            $diceCount = $diceCounts[$diceFace];
            $this->resolveNumberDice($playerId, $diceFace, $diceCount);
        }

        if ($this->isWickednessExpansion() && $this->canTakeWickednessTile($playerId) > 0) {
            $this->gamestate->nextState('takeWickednessTile');
        } else {
            $this->jumpToState($this->getRedirectAfterResolveNumberDice());
        }        
    }

    function getRedirectAfterResolveNumberDice() {
        $playerId = $this->getActivePlayerId();
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

    function stResolveHeartDice() {
        $playerId = $this->getActivePlayerId();
        
        $diceCounts = $this->getGlobalVariable(DICE_COUNTS, true);

        $diceCount = $diceCounts[4];
        if ($diceCount > 0) {
            $this->resolveHealthDice($playerId, $diceCount);
        }
        $this->gamestate->nextState('next');
    }

    function stResolveEnergyDice() {
        $playerId = $this->getActivePlayerId();
        
        $diceCounts = $this->getGlobalVariable(DICE_COUNTS, true);

        $diceCount = $diceCounts[5];
        if ($diceCount > 0) {
            $this->resolveEnergyDice($playerId, $diceCount);
        }

        $this->gamestate->nextState('next');
    }

    function stResolveSmashDice() {
        $playerId = $this->getActivePlayerId();

        if ($this->countCardOfType($playerId, HIBERNATION_CARD) > 0) {
            $this->setGameStateValue(STATE_AFTER_RESOLVE, ST_ENTER_TOKYO_APPLY_BURROWING);
            $this->gamestate->nextState('next');
            return;
        }
        
        $diceCounts = $this->getGlobalVariable(DICE_COUNTS, true);

        if ($this->canUseSymbol($playerId, 6)) {
            $diceCount = $diceCounts[6];
        } else {
            $diceCount = 0;
        }
        
        $redirects = false;
        if ($diceCount > 0) {
            $playerId = $this->getActivePlayerId();
            $redirects = $this->resolveSmashDice($playerId, $diceCount);
        } else {
            $this->setGameStateValue(STATE_AFTER_RESOLVE, ST_ENTER_TOKYO_APPLY_BURROWING);
        }

        if (!$redirects) {    
            $this->gamestate->nextState('next');
        }
    }

    function stResolveSkullDice() {   
        $playerId = $this->getActivePlayerId();

        $pickEvolutionCards = false;        
        if ($this->isPowerUpExpansion()) {
            $dice = $this->getPlayerRolledDice($playerId, true, true, false);
            $diceCounts = $this->getRolledDiceCounts($playerId, $dice, false);
            $pickEvolutionCards = $diceCounts[4] >= 3;
        } 

        $nextState = $pickEvolutionCards ? ST_CHOOSE_EVOLUTION_CARD : intval($this->getGameStateValue(STATE_AFTER_RESOLVE));

        if ($this->countCardOfType($playerId, HIBERNATION_CARD) > 0) {
            $this->gamestate->jumpToState($nextState);
            return;
        }

        $diceCounts = $this->getGlobalVariable(DICE_COUNTS, true);
        $redirects = false;

        $damages = [];

        if ($this->isCybertoothExpansion() && $diceCounts[7] > 0) {
            $damages[] = new Damage($playerId, $diceCounts[7], $playerId, 0, 0, 0);
        }

        if ($this->isAnubisExpansion()) {
            $curseCardType = $this->getCurseCardType();

            if ($curseCardType == FALSE_BLESSING_CURSE_CARD) {
                $dice = $this->getPlayerRolledDice($playerId, true, false, false);
                $diceFaces = [];        
                foreach ($dice as $die) {
                    if ($die->type === 0 || $die->type === 1) {
                        $diceFaces[$this->getDiceFaceType($die)] = true; 
                    }
                }

                $facesCount = count(array_keys($diceFaces));
                
                $damages[] = new Damage($playerId, $facesCount, 0, 1000 + FALSE_BLESSING_CURSE_CARD, 0, 0);
            }
        }

        if (count($damages) > 0) {
            $redirects = $this->resolveDamages($damages, $nextState);
        }
        
        if (!$redirects) {
            $this->gamestate->jumpToState($nextState);
        }
    }
}
