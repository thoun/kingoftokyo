<?php

namespace KOT\States;

require_once(__DIR__.'/objects/dice.php');
require_once(__DIR__.'/objects/player-intervention.php');
require_once(__DIR__.'/objects/damage.php');

use KOT\Objects\Dice;
use KOT\Objects\ChangeActivePlayerDieIntervention;
use KOT\Objects\Damage;

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
        $playerId = self::getActivePlayerId();

        $canChangeWithCards = $this->canChangeDie($this->getChangeDieCards($playerId));
        $canRetrow3 = intval(self::getGameStateValue(PSYCHIC_PROBE_ROLLED_A_3)) > 0 && $this->countCardOfType($playerId, BACKGROUND_DWELLER_CARD) > 0;
        
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

    function stResolveDice() {
        $this->updateKillPlayersScoreAux();
        
        $playerId = self::getActivePlayerId();
        self::giveExtraTime($playerId);

        self::DbQuery("UPDATE dice SET `locked` = true, `rolled` = false");

        $playerInTokyo = $this->inTokyo($playerId);
        $dice = $this->getDice($this->getDiceNumber($playerId));
        $diceValues = array_map(function($idie) { return $idie->value; }, $dice);
        sort($diceValues);

        $diceStr = '';
        foreach($diceValues as $dieValue) {
            $diceStr .= $this->getDieFaceLogName($dieValue);
        }

        self::notifyAllPlayers("resolvePlayerDice", clienttranslate('${player_name} resolves dice ${dice}'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'dice' => $diceStr,
        ]);

        $smashTokyo = false;

        $diceCounts = [];
        for ($diceFace = 1; $diceFace <= 6; $diceFace++) {
            $diceCounts[$diceFace] = count(array_values(array_filter($diceValues, function($dice) use ($diceFace) { return $dice == $diceFace; })));
        }

        $detail = $this->addSmashesFromCards($playerId, $diceCounts, $playerInTokyo);
        $diceCounts[6] += $detail->addedSmashes;

        if ($detail->addedSmashes > 0) {
            $diceStr = '';
            for ($i=0; $i<$detail->addedSmashes; $i++) { 
                $diceStr .= $this->getDieFaceLogName(6); 
            }
            
            $cardNamesStr = implode(', ', $detail->cardsAddingSmashes);

            self::notifyAllPlayers("resolvePlayerDiceAddedDice", clienttranslate('${player_name} adds ${dice} with ${card_name}'), [
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
            if ($diceCounts[4] >= 1 && $diceCounts[5] >= 1 && $diceCounts[6] >= 1) { // dice 1-2-3 check with previous if
                $countCompleteDestruction = $this->countCardOfType($playerId, COMPLETE_DESTRUCTION_CARD);
                if ($countCompleteDestruction > 0) {
                    $this->applyGetPoints($playerId, 9 * $countCompleteDestruction, COMPLETE_DESTRUCTION_CARD);
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

        $this->setGlobalVariable(FIRE_BREATHING_DAMAGES, $fireBreathingDamages);
        $this->setGlobalVariable(DICE_COUNTS, $diceCounts);

        if ($diceCounts[1] >= 4 && $this->isKingKongExpansion()) {
            $this->getNewTokyoTowerLevel($playerId);
        }

        $this->gamestate->nextState('next');
    }

    function stResolveNumberDice() {
        $playerId = self::getActivePlayerId();

        $diceCounts = $this->getGlobalVariable(DICE_COUNTS, true);

        for ($diceFace = 1; $diceFace <= 3; $diceFace++) {
            $diceCount = $diceCounts[$diceFace];
            $this->resolveNumberDice($playerId, $diceFace, $diceCount);
        }

        $canSelectHeartDiceUse = false;
        if ($diceCounts[4] > 0) {
            $selectHeartDiceUse = $this->getSelectHeartDiceUse($playerId);
            $inTokyo = $this->inTokyo($playerId);

            $canRemoveToken = ($selectHeartDiceUse['shrinkRayTokens'] > 0 || $selectHeartDiceUse['poisonTokens'] > 0) && !$inTokyo;

            $canSelectHeartDiceUse = ($selectHeartDiceUse['hasHealingRay'] && count($selectHeartDiceUse['healablePlayers']) > 0) || $canRemoveToken;
        }

        if ($canSelectHeartDiceUse) {
            $this->gamestate->nextState('nextAction');
        } else {
            $this->gamestate->nextState('next');
        }
    }

    function stResolveHeartDice() {
        $playerId = self::getActivePlayerId();
        
        $diceCounts = $this->getGlobalVariable(DICE_COUNTS, true);

        $diceCount = $diceCounts[4];
        if ($diceCount > 0) {
            $this->resolveHealthDice($playerId, $diceCount);
        }
        $this->gamestate->nextState('next');
    }

    function stResolveEnergyDice() {
        $playerId = self::getActivePlayerId();
        
        $diceCounts = $this->getGlobalVariable(DICE_COUNTS, true);

        $diceCount = $diceCounts[5];
        if ($diceCount > 0) {
            $this->resolveEnergyDice($playerId, $diceCount);
        }

        $this->gamestate->nextState('next');
    }

    function stResolveSmashDice() {
        $playerId = self::getActivePlayerId();
        
        $diceCounts = $this->getGlobalVariable(DICE_COUNTS, true);

        $diceCount = $diceCounts[6];

        $nextState = 'enterTokyo';
        $smashTokyo = false;

        if ($diceCount > 0) {
            $nextState = $this->resolveSmashDice($playerId, $diceCount);
        }
        
        if ($nextState != null) {
            $this->gamestate->nextState($nextState);
        }
    }
}
