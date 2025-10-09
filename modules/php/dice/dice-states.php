<?php

namespace KOT\States;

require_once(__DIR__.'/../Objects/damage.php');

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

    function addHighTideDice(int $playerId, int $diceCount) {
        $isPowerUpExpansion = $this->powerUpExpansion->isActive();
        
        $highTideEvolutions = $isPowerUpExpansion ? $this->getEvolutionsOfType($playerId, HIGH_TIDE_EVOLUTION) : [];
        
        $addedHearts = 0;
        $cardsAddingHearts = [];
        if (count($highTideEvolutions) > 0) {
            $addedHearts += $diceCount;
            
            foreach($highTideEvolutions as $evolution) {
                $cardsAddingHearts[] = 3000 + $evolution->type;
            }

            $this->removeEvolutions($playerId, $highTideEvolutions);
        }
        $diceCount += $addedHearts;        

        if ($addedHearts) {
            $diceStr = '';
            for ($i=0; $i<$addedHearts; $i++) { 
                $diceStr .= $this->getDieFaceLogName(4, 0); 
            }
            
            $cardNamesStr = implode(', ', $cardsAddingHearts);

            $this->notifyAllPlayers("resolvePlayerDiceAddedDice", clienttranslate('${player_name} adds ${dice} with ${card_name}'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerNameById($playerId),
                'dice' => $diceStr,
                'card_name' => $cardNamesStr,
            ]);
        }

        return $diceCount;
    }

    function stResolveHeartDiceAction() {
        $playerId = $this->getActivePlayerId();
        
        $diceCounts = $this->getGlobalVariable(DICE_COUNTS, true);

        $diceCount = $this->addHighTideDice($playerId, $diceCounts[4]);

        if ($diceCount > $diceCounts[4]) {
            $diceCounts[4] = $diceCount;
            $this->setGlobalVariable(DICE_COUNTS, $diceCounts);
        }
    }

    function resolveSmashDiceState(array $playersSmashesWithReducedDamage = []) {
        $playerId = $this->getActivePlayerId();

        if ($this->countCardOfType($playerId, HIBERNATION_CARD) > 0) {
            $this->setGameStateValue(STATE_AFTER_RESOLVE, ST_ENTER_TOKYO_APPLY_BURROWING);
            $this->goToState(ST_RESOLVE_SKULL_DICE);
            return;
        }

        $diceCounts = $this->getGlobalVariable(DICE_COUNTS, true);

        $diceCount = ($this->canUseSymbol($playerId, 6) && $this->canUseFace($playerId, 6))
            ? $diceCounts[6]
            : 0;

        $this->resolveSmashDice($playerId, $diceCount, $playersSmashesWithReducedDamage);
    }

}
