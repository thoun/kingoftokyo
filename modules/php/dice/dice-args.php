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
            'frozenFaces' => $this->frozenFaces($playerId),
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
            $heartOfTheRabbitEvolutions = $this->getEvolutionsOfType($playerId, HEART_OF_THE_RABBIT_EVOLUTION, false, true);

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
            foreach($heartOfTheRabbitEvolutions as $heartOfTheRabbitEvolution) {
                if (!in_array(3000 + $heartOfTheRabbitEvolution->id, $usedCards)) {
                    $canRoll = true;
                }
            }

            $hasBackgroundDweller = $this->countCardOfType($playerId, BACKGROUND_DWELLER_CARD) > 0;
            $hasDice3 = $intervention->lastRolledDie != null && $intervention->lastRolledDie->value == 3;
        }

        $dice = $this->getPlayerRolledDice($activePlayerId, true, true, true);
        $canReroll = false;
        if ($this->anubisExpansion->isActive()) {
            $curseCardType = $this->anubisExpansion->getCurseCardType();
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
            'frozenFaces' => $this->frozenFaces($activePlayerId),
            'rethrow3' => [
                'hasCard' => $hasBackgroundDweller,
                'hasDice3' => $hasDice3,
            ],
        ];
    }

    function argResolveHeartDiceAction() {
        $playerId = $this->getActivePlayerId();

        $diceCounts = $this->getGlobalVariable(DICE_COUNTS, true);

        $diceCount = $this->addHighTideDice($playerId, $diceCounts[4]);

        if ($diceCount > $diceCounts[4]) {
            $diceCounts[4] = $diceCount;
            $this->setGlobalVariable(DICE_COUNTS, $diceCounts);
        }

        if ($diceCount > 0) {
            $dice = $this->getPlayerRolledDice($playerId, false, false, false);
    
            $selectHeartDiceUseArg = $this->getSelectHeartDiceUse($playerId);  

            $canHealWithDice = $this->canHealWithDice($playerId);

            $canSelectHeartDiceUse = $selectHeartDiceUseArg['hasHealingRay'] || (($selectHeartDiceUseArg['shrinkRayTokens'] > 0 || $selectHeartDiceUseArg['poisonTokens'] > 0) && $canHealWithDice);

            $diceArg = $canSelectHeartDiceUse ? [
                'dice' => $dice,
                'canHealWithDice' => $canHealWithDice,
                'frozenFaces' => $this->frozenFaces($playerId),
            ] : [ 'skipped' => true ];
    
            return $selectHeartDiceUseArg + $diceArg;
        }
        return [ 'skipped' => true ];
    }

    function argResolveDice() {
        $activePlayerId = $this->getActivePlayerId();
        $dice = $this->getPlayerRolledDice($activePlayerId, true, true, false);

        $isInHibernation = $this->countCardOfType($activePlayerId, HIBERNATION_CARD) > 0;
        $canLeaveHibernation = $isInHibernation && $this->canLeaveHibernation($activePlayerId, $dice);

        return [
            'dice' => $this->getPlayerRolledDice($activePlayerId, true, true, false),
            'canHealWithDice' => $this->canHealWithDice($activePlayerId),
            'frozenFaces' => $this->frozenFaces($activePlayerId),
            'selectableDice' => [],
            'isInHibernation' => $isInHibernation,
            'canLeaveHibernation' => $canLeaveHibernation,
        ];
    }

    function argResolveSmashDiceAction() {
        $playerId = $this->getActivePlayerId();

        $diceCounts = $this->getGlobalVariable(DICE_COUNTS, true);
        $canUsePlayWithYourFood = $this->canUsePlayWithYourFood($playerId, $diceCounts);

        if ($canUsePlayWithYourFood !== null) {
            $dice = $this->getPlayerRolledDice($playerId, false, false, false);
            $canHealWithDice = $this->canHealWithDice($playerId);

            $diceArg = $canUsePlayWithYourFood ? [
                'dice' => $dice,
                'canHealWithDice' => $canHealWithDice,
                'frozenFaces' => $this->frozenFaces($playerId),
                'canUsePlayWithYourFood' => true,
                'willBeWoundedIds' => $canUsePlayWithYourFood,
            ] : [ 'skipped' => true ];
    
            return $diceArg;
        }
        return [ 'skipped' => true ];
    }

    function argPrepareResolveDice() {
        $playerId = $this->getActivePlayerId();

        $hasEncasedInIce = $this->powerUpExpansion->isActive() && $this->countEvolutionOfType($playerId, ENCASED_IN_ICE_EVOLUTION) > 0;

        $dice = $this->getPlayerRolledDice($playerId, true, true, false);
        $selectableDice = $this->getSelectableDice($dice, false, false);
        $canHealWithDice = $this->canHealWithDice($playerId);

        return [ 
            'dice' => $dice,
            'canHealWithDice' => $canHealWithDice,
            'frozenFaces' => $this->frozenFaces($playerId),
            'selectableDice' => $selectableDice,
            'hasEncasedInIce' => $hasEncasedInIce,
        ];
    }

}
