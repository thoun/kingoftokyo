<?php

namespace KOT\States;

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

/**
 * @mixin \Bga\Games\KingOfTokyo\Game
 */
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

            $this->notify->all("resolvePlayerDiceAddedDice", clienttranslate('${player_name} adds ${dice} with ${card_name}'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerNameById($playerId),
                'dice' => $diceStr,
                'card_name' => $cardNamesStr,
            ]);
        }

        return $diceCount;
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
