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

    function stThrowDice() {
        // disabled so player can see last roll
        /*if ($this->autoSkipImpossibleActions() && !$this->argThrowDice()['hasActions']) {
            // skip state
            $this->actGoToChangeDie();
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
        $playerId = $this->getActivePlayerId();
        $hasEncasedInIce = $this->powerUpExpansion->isActive() && $this->countEvolutionOfType($playerId, ENCASED_IN_ICE_EVOLUTION) > 0;

        if ($hasEncasedInIce) {
            $potentialEnergy = $this->getPlayerPotentialEnergy($playerId);

            if ($potentialEnergy < 1) {
                $hasEncasedInIce = false;
            }
        }

        if (!$hasEncasedInIce) {
            $this->goToState($this->redirectAfterPrepareResolveDice());
        }
    }

    function applyResolveDice(int $playerId) {
        $isPowerUpExpansion = $this->powerUpExpansion->isActive();

        $playerInTokyo = $this->inTokyo($playerId);
        $dice = $this->getPlayerRolledDice($playerId, true, true, false);
        usort($dice, "static::sortDieFunction");

        $diceStr = '';
        foreach($dice as $die) {
            $diceStr .= $this->getDieFaceLogName($die->value, $die->type);
        }

        $this->notifyAllPlayers("resolvePlayerDice", clienttranslate('${player_name} resolves dice ${dice}'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerNameById($playerId),
            'dice' => $diceStr,
        ]);

        $diceCounts = $this->getRolledDiceCounts($playerId, $dice, false);

        $diceCountsWithoutAddedSmashes = $diceCounts; // clone

        $detail = $this->addSmashesFromCards($playerId, $diceCounts, $playerInTokyo);
        $diceAndCardsCounts = $diceCounts; // copy
        $diceAndCardsCounts[6] += $detail->addedSmashes;

        if ($detail->addedSmashes > 0) {
            $diceStr = '';
            for ($i=0; $i<$detail->addedSmashes; $i++) { 
                $diceStr .= $this->getDieFaceLogName(6, 0); 
            }
            
            $cardNamesStr = implode(', ', $detail->cardsAddingSmashes);

            $this->notifyAllPlayers("resolvePlayerDiceAddedDice", clienttranslate('${player_name} adds ${dice} with ${card_name}'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerNameById($playerId),
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
            if (
                $rolledFaces[41] >= 1 
                && $rolledFaces[51] >= 1 
                && ($rolledFaces[61] >= 1 || $detail->addedSmashes > 0)
            ) { // dice 1-2-3 check with previous if
                $countCompleteDestruction = $this->countCardOfType($playerId, COMPLETE_DESTRUCTION_CARD);
                if ($countCompleteDestruction > 0) {
                    $this->applyGetPoints($playerId, 9 * $countCompleteDestruction, COMPLETE_DESTRUCTION_CARD);
                }

                if ($this->powerUpExpansion->isActive()) {
                    $countPandaExpress = $this->countEvolutionOfType($playerId, PANDA_EXPRESS_EVOLUTION);
                    if ($countPandaExpress > 0) {
                        $this->applyGetPoints($playerId, 2 * $countPandaExpress, 3000 + PANDA_EXPRESS_EVOLUTION);
                        if ($this->mindbugExpansion->canGetExtraTurn()) {
                            $this->setGameStateValue(PANDA_EXPRESS_EXTRA_TURN, 1);
                        }
                    }
                }
            }
        }

        $fireBreathingDamages = [];
        // fire breathing
        if ($diceAndCardsCounts[6] >= 1) {
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

        if ($diceCounts[1] >= 4 && $this->kingKongExpansion->isActive() && $this->inTokyo($playerId) && $this->canUseSymbol($playerId, 1) && $this->canUseFace($playerId, 1)) {
            $this->kingKongExpansion->getNewTokyoTowerLevel($playerId);
        }
        
        $isCthulhuExpansion = $this->cthulhuExpansion->isActive();
        $fourOfAKind = false;
        $fourOfAKindWithCards = false;
        $flamingAuraDamages = [];
        for ($diceFace = 1; $diceFace <= 6; $diceFace++) {
            $canUseSymbolAndFace = $this->canUseSymbol($playerId, $diceFace) && $this->canUseFace($playerId, $diceFace);
            if ($diceAndCardsCounts[$diceFace] >= 4 && $canUseSymbolAndFace) {
                $fourOfAKindWithCards = true;
                if ($isCthulhuExpansion) {
                    $this->cthulhuExpansion->applyGetCultist($playerId, $diceFace);
                }
            }
            if ($diceCounts[$diceFace] >= 4 && $canUseSymbolAndFace) {
                $fourOfAKind = true;

                $countDrainingRay = $this->countCardOfType($playerId, DRAINING_RAY_CARD);
                if ($countDrainingRay > 0) {
                    $playersIds = $this->getPlayersIdsWithMaxColumn('player_score');
                    foreach ($playersIds as $pId) {
                        if ($pId != $playerId) {
                            $this->applyLosePoints($pId, $countDrainingRay, DRAINING_RAY_CARD);
                            $this->applyGetPoints($playerId, $countDrainingRay, DRAINING_RAY_CARD);
                        }
                    }
                }

                $countFlamingAura = $this->countCardOfType($playerId, FLAMING_AURA_CARD);
                if ($countFlamingAura > 0) {
                    $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                    foreach ($otherPlayersIds as $otherPlayerId) {
                        $flamingAuraDamages[$otherPlayerId] = $countFlamingAura;
                    }
                }
            }
        }
        if ($isPowerUpExpansion && $fourOfAKindWithCards) {
            $count8thWonderOfTheWorld = $this->countEvolutionOfType($playerId, EIGHTH_WONDER_OF_THE_WORLD_EVOLUTION);
            if ($count8thWonderOfTheWorld > 0) {
                $this->applyGetPoints($playerId, $count8thWonderOfTheWorld, 3000 + EIGHTH_WONDER_OF_THE_WORLD_EVOLUTION);
            }
        }
        
        $funnyLookingButDangerousDamages = [];
        if ($isPowerUpExpansion) {
            if ($diceCounts[4] >= 3) {
                $countPandarwinism = $this->countEvolutionOfType($playerId, PANDARWINISM_EVOLUTION);
                if ($countPandarwinism > 0) {
                    $this->applyGetPoints($playerId, ($diceCounts[4] - 2) * $countPandarwinism, 3000 + PANDARWINISM_EVOLUTION);
                }
            }

            if ($diceCounts[2] >= 3) {
                $countFunnyLookingButDangerous = $this->countEvolutionOfType($playerId, FUNNY_LOOKING_BUT_DANGEROUS_EVOLUTION);
                if ($countFunnyLookingButDangerous > 0) {
                    $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                    foreach ($otherPlayersIds as $otherPlayerId) {
                        $funnyLookingButDangerousDamages[$otherPlayerId] = $countFunnyLookingButDangerous;
                    }
                }
            }

            if ($diceCountsWithoutAddedSmashes[6] >= 1) {
                $energySwordEvolutions = $this->getEvolutionsOfType($playerId, ENERGY_SWORD_EVOLUTION);
                $countEnergySword = count(array_filter($energySwordEvolutions, fn($evolution) => $evolution->tokens > 0));
                if ($countEnergySword > 0) {
                    $this->applyGetEnergy($playerId, $diceCountsWithoutAddedSmashes[6] * $countEnergySword, 3000 + ENERGY_SWORD_EVOLUTION);
                }
            }
        }

        $this->setGlobalVariable(FIRE_BREATHING_DAMAGES, $fireBreathingDamages);
        $this->setGlobalVariable(FUNNY_LOOKING_BUT_DANGEROUS_DAMAGES, $funnyLookingButDangerousDamages);
        $this->setGlobalVariable(FLAMING_AURA_DAMAGES, $flamingAuraDamages);
        $this->setGlobalVariable(DICE_COUNTS, $diceAndCardsCounts);
    }

    function stResolveDice() {
        $this->updateKillPlayersScoreAux();
        
        $playerId = $this->getActivePlayerId();
        $this->giveExtraTime($playerId);

        $this->DbQuery("UPDATE dice SET `locked` = true, `rolled` = false");

        $args = $this->argResolveDice();
        if ($args['isInHibernation']) {
            if (!$args['canLeaveHibernation']) {
                $this->applyResolveDice($playerId);
                
                $this->goToState($this->redirectAfterResolveDice());
            }
        } else {
            $this->applyResolveDice($playerId);
            
            $this->goToState($this->redirectAfterResolveDice());
        }
    }

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
