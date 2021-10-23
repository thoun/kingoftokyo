<?php

namespace KOT\States;

require_once(__DIR__.'/objects/dice.php');
require_once(__DIR__.'/objects/player-intervention.php');
require_once(__DIR__.'/objects/damage.php');

use KOT\Objects\Dice;
use KOT\Objects\ChangeActivePlayerDieIntervention;
use KOT\Objects\Damage;

trait DiceArgTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    function argThrowDice() {
        $playerId = self::getActivePlayerId();
        $diceNumber = $this->getDiceNumber($playerId);
        $dice = $this->getDice($diceNumber);

        $throwNumber = intval(self::getGameStateValue('throwNumber'));
        $maxThrowNumber = $this->getThrowNumber($playerId);

        $hasEnergyDrink = $this->countCardOfType($playerId, ENERGY_DRINK_CARD) > 0; // Energy drink
        $playerEnergy = null;
        if ($hasEnergyDrink) {
            $playerEnergy = $this->getPlayerEnergy($playerId);
        }

        $hasBackgroundDweller = $this->countCardOfType($playerId, BACKGROUND_DWELLER_CARD) > 0; // Background Dweller
        $hasDice3 = null;
        if ($hasBackgroundDweller) {
            $hasDice3 = $this->getFirst3Dice($diceNumber) != null;
        }

        $smokeCloudsTokens = 0;
        $smokeCloudCards = $this->getCardsOfType($playerId, SMOKE_CLOUD_CARD); // Smoke Cloud
        foreach($smokeCloudCards as $smokeCloudCard) {
            $smokeCloudsTokens += $smokeCloudCard->tokens;
        }
        $hasSmokeCloud = $smokeCloudsTokens > 0;

        $hasActions = $throwNumber < $maxThrowNumber || ($hasEnergyDrink && $playerEnergy >= 1) || $hasDice3 || $hasSmokeCloud;
    
        // return values:
        return [
            'throwNumber' => $throwNumber,
            'maxThrowNumber' => $maxThrowNumber,
            'dice' => $dice,
            'inTokyo' => $this->inTokyo($playerId),
            'energyDrink' => [
                'hasCard' => $hasEnergyDrink,
                'playerEnergy' => $playerEnergy,
            ],
            'rethrow3' => [
                'hasCard' => $hasBackgroundDweller,
                'hasDice3' => $hasDice3,
            ],
            'hasSmokeCloud' => $hasSmokeCloud,
            'hasActions' => $hasActions,
        ];
    }

    function argChangeDie() {
        $playerId = self::getActivePlayerId();

        $cardsArg = $this->getChangeDieCards($playerId);

        $hasBackgroundDweller = $this->countCardOfType($playerId, BACKGROUND_DWELLER_CARD) > 0;
        $canRetrow3 = $hasBackgroundDweller && intval(self::getGameStateValue(PSYCHIC_PROBE_ROLLED_A_3)) > 0;

        $diceNumber = $this->getDiceNumber($playerId);
        $diceArg = [
            'dice' => $this->getDice($diceNumber),
            'inTokyo' => $this->inTokyo($playerId),
            'rethrow3' => [
                'hasCard' => $hasBackgroundDweller,
                'hasDice3' => $canRetrow3,
            ],
        ];

        return $cardsArg + $diceArg;
    }

    function argChangeActivePlayerDie($intervention = null) {
        if ($intervention == null) {
            $intervention = $this->getGlobalVariable(CHANGE_ACTIVE_PLAYER_DIE_INTERVENTION);
        }
        $activePlayerId = $intervention->activePlayerId;

        $canRoll = true;
        $hasDice3 = false;
        $hasBackgroundDweller = false;

        $playerId = $intervention && count($intervention->remainingPlayersId) > 0 ? $intervention->remainingPlayersId[0] : null;
        if ($playerId) {
            $psychicProbeCards = $this->getCardsOfType($playerId, PSYCHIC_PROBE_CARD);
            $witchCards = $this->getCardsOfType($playerId, WITCH_CARD);
            $canRoll = false;
            $usedCards = $this->getUsedCard();
            foreach($psychicProbeCards as $psychicProbeCard) {
                if (!in_array($psychicProbeCard->id, $usedCards)) {
                    $canRoll = true;
                }
            }
            foreach($witchCards as $witchCard) {
                if (!in_array($witchCard->id, $usedCards)) {
                    $canRoll = true;
                }
            }

            $hasBackgroundDweller = $this->countCardOfType($playerId, BACKGROUND_DWELLER_CARD) > 0;
            $hasDice3 = $intervention->lastRolledDie != null && $intervention->lastRolledDie->value == 3;
        }

        $diceNumber = $this->getDiceNumber($activePlayerId);
        return [
            'dice' => $this->getDice($diceNumber),
            'inTokyo' => $this->inTokyo($activePlayerId),
            'canRoll' => $canRoll,
            'rethrow3' => [
                'hasCard' => $hasBackgroundDweller,
                'hasDice3' => $hasDice3,
            ],
        ];
    }

    function argResolveHeartDiceAction() {
        $playerId = self::getActivePlayerId();

        $diceCounts = $this->getGlobalVariable(DICE_COUNTS, true);
        $diceCount = $diceCounts[4];

        if ($diceCount > 0) {  

            $diceNumber = $this->getDiceNumber($playerId);
            $dice = $this->getDice($diceNumber);
    
            $selectHeartDiceUseArg = $this->getSelectHeartDiceUse($playerId);  

            $inTokyo = $this->inTokyo($playerId);

            $canSelectHeartDiceUse = $selectHeartDiceUseArg['hasHealingRay'] || (($selectHeartDiceUseArg['shrinkRayTokens'] > 0 || $selectHeartDiceUseArg['poisonTokens'] > 0) && !$inTokyo);

            $diceArg = $canSelectHeartDiceUse ? [
                'dice' => $dice,
                'inTokyo' => $inTokyo,
            ] : [ 'skipped' => true ];
    
            return $selectHeartDiceUseArg + $diceArg;
        }
        return [ 'skipped' => true ];
    }

}
