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

}
