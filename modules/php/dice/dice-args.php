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

}
