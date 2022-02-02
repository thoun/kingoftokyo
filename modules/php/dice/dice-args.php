<?php

namespace KOT\States;

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
        $playerId = $this->getActivePlayerId();
        $dice = $this->getPlayerRolledDice($playerId, true, true, true);

        $throwNumber = intval($this->getGameStateValue('throwNumber'));
        $maxThrowNumber = $this->getRollNumber($playerId);

        $hasEnergyDrink = $this->countCardOfType($playerId, ENERGY_DRINK_CARD) > 0; // Energy drink
        $playerEnergy = null;
        if ($hasEnergyDrink) {
            $playerEnergy = $this->getPlayerEnergy($playerId);
        }

        $hasBackgroundDweller = $this->countCardOfType($playerId, BACKGROUND_DWELLER_CARD) > 0; // Background Dweller
        $hasDice3 = null;
        if ($hasBackgroundDweller) {
            $hasDice3 = $this->getFirst3Die($playerId) != null;
        }

        $smokeCloudsTokens = 0;
        $smokeCloudCards = $this->getCardsOfType($playerId, SMOKE_CLOUD_CARD); // Smoke Cloud
        foreach($smokeCloudCards as $smokeCloudCard) {
            $smokeCloudsTokens += $smokeCloudCard->tokens;
        }
        $hasSmokeCloud = $smokeCloudsTokens > 0;
        $hasCultist = $this->isCthulhuExpansion() && $this->getPlayerCultists($playerId) > 0;

        $hasActions = $throwNumber < $maxThrowNumber || ($hasEnergyDrink && $playerEnergy >= 1) || $hasDice3 || $hasSmokeCloud || $hasCultist;

        $selectableDice = $this->getSelectableDice($dice, false, true);
    
        // return values:
        return [
            'throwNumber' => $throwNumber,
            'maxThrowNumber' => $maxThrowNumber,
            'dice' => $dice,
            'selectableDice' => $selectableDice,
            'canHealWithDice' => $this->canHealWithDice($playerId),
            'energyDrink' => [
                'hasCard' => $hasEnergyDrink,
                'playerEnergy' => $playerEnergy,
            ],
            'rethrow3' => [
                'hasCard' => $hasBackgroundDweller,
                'hasDice3' => $hasDice3,
            ],
            'hasSmokeCloud' => $hasSmokeCloud,
            'hasCultist' => $hasCultist,
            'hasActions' => $hasActions,
        ];
    }

    function argChangeDie() {
        $playerId = intval($this->getActivePlayerId());

        $cardsArg = $this->getChangeDieCards($playerId);

        $hasBackgroundDweller = $this->countCardOfType($playerId, BACKGROUND_DWELLER_CARD) > 0;
        $canRetrow3 = $hasBackgroundDweller && intval($this->getGameStateValue(PSYCHIC_PROBE_ROLLED_A_3)) > 0;

        $dice = $this->getPlayerRolledDice($playerId, true, true, true);
        $selectableDice = $this->getSelectableDice($dice, true, false);

        $diceArg = [
            'playerId' => $playerId,
            'dice' => $dice,
            'selectableDice' => $selectableDice,
            'canHealWithDice' => $this->canHealWithDice($playerId),
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

        $dice = $this->getPlayerRolledDice($activePlayerId, true, true, true);
        $canReroll = false;
        if ($this->isAnubisExpansion()) {
            $curseCardType = $this->getCurseCardType();
            if ($curseCardType === VENGEANCE_OF_HORUS_CURSE_CARD) {
                $canReroll = false;
            } else if ($curseCardType === SCRIBE_S_PERSEVERANCE_CURSE_CARD) {
                $canReroll = true;
            }
        }
        
        $selectableDice = $canRoll ? $this->getSelectableDice($dice, $canReroll, false) : [];

        return [
            'dice' => $dice,
            'selectableDice' => $selectableDice,
            'canHealWithDice' => $this->canHealWithDice($activePlayerId),
            'rethrow3' => [
                'hasCard' => $hasBackgroundDweller,
                'hasDice3' => $hasDice3,
            ],
        ];
    }

    function argResolveHeartDiceAction() {
        $playerId = $this->getActivePlayerId();

        $diceCounts = $this->getGlobalVariable(DICE_COUNTS, true);
        $diceCount = $diceCounts[4];

        if ($diceCount > 0) {
            $dice = $this->getPlayerRolledDice($playerId, false, false, false);
    
            $selectHeartDiceUseArg = $this->getSelectHeartDiceUse($playerId);  

            $canHealWithDice = $this->canHealWithDice($playerId);

            $canSelectHeartDiceUse = $selectHeartDiceUseArg['hasHealingRay'] || (($selectHeartDiceUseArg['shrinkRayTokens'] > 0 || $selectHeartDiceUseArg['poisonTokens'] > 0) && $canHealWithDice);

            $diceArg = $canSelectHeartDiceUse ? [
                'dice' => $dice,
                'canHealWithDice' => $this->canHealWithDice($playerId),
            ] : [ 'skipped' => true ];
    
            return $selectHeartDiceUseArg + $diceArg;
        }
        return [ 'skipped' => true ];
    }

    function argResolveNumberDice() {
        $activePlayerId = $this->getActivePlayerId();

        return [
            'dice' => $this->getPlayerRolledDice($activePlayerId, true, true, false),
            'canHealWithDice' => $this->canHealWithDice($activePlayerId),
        ];
    }

}
