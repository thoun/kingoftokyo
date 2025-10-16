<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class ResolveDice extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_RESOLVE_DICE,
            type: StateType::ACTIVE_PLAYER,
            name: 'resolveDice',
            description: '',
            descriptionMyTurn: '',
        );
    }

    public function getArgs(int $activePlayerId): array {
        $dice = $this->game->getPlayerRolledDice($activePlayerId, true, true, false);

        $isInHibernation = $this->game->countCardOfType($activePlayerId, HIBERNATION_CARD) > 0;
        $canLeaveHibernation = $isInHibernation && $this->canLeaveHibernation($activePlayerId, $dice);

        return [
            'dice' => $this->game->getPlayerRolledDice($activePlayerId, true, true, false),
            'canHealWithDice' => $this->game->canHealWithDice($activePlayerId),
            'frozenFaces' => $this->game->frozenFaces($activePlayerId),
            'selectableDice' => [],
            'isInHibernation' => $isInHibernation,
            'canLeaveHibernation' => $canLeaveHibernation,
        ];
    }

    public function onEnteringState(int $activePlayerId, array $args): void {
        $this->game->updateKillPlayersScoreAux();

        $this->game->giveExtraTime($activePlayerId);

        $this->game->DbQuery("UPDATE dice SET `locked` = true, `rolled` = false");

        if (!array_key_exists('isInHibernation', $args)) {
            $args = $this->getArgs($activePlayerId);
        }

        $isInHibernation = (bool)($args['isInHibernation'] ?? false);
        $canLeaveHibernation = (bool)($args['canLeaveHibernation'] ?? false);

        if (!$isInHibernation || !$canLeaveHibernation) {
            $this->applyResolveDice($activePlayerId);
            $this->game->goToState($this->game->redirectAfterResolveDice());
        }
    }

  	#[PossibleAction]
    public function actStayInHibernation(int $activePlayerId) {
        $this->applyResolveDice($activePlayerId);
        
        $this->game->goToState($this->game->redirectAfterResolveDice());
    }
  	
  	#[PossibleAction]
    public function actLeaveHibernation(int $activePlayerId) {
        $cards = $this->game->powerCards->getCardsOfType(HIBERNATION_CARD);
        $this->game->removeCards($activePlayerId, $cards);

        $this->applyResolveDice($activePlayerId);

        $this->game->goToState($this->game->redirectAfterResolveDice());
    }

    public function zombie(int $playerId): void {
        $this->actLeaveHibernation($playerId);
    }

    function applyResolveDice(int $playerId) {
        $isPowerUpExpansion = $this->game->powerUpExpansion->isActive();

        $playerInTokyo = $this->game->inTokyo($playerId);
        $dice = $this->game->getPlayerRolledDice($playerId, true, true, false);
        usort($dice, [Game::class, 'sortDieFunction']);

        $diceStr = '';
        foreach($dice as $die) {
            $diceStr .= $this->game->getDieFaceLogName($die->value, $die->type);
        }

        $this->notify->all("resolvePlayerDice", clienttranslate('${player_name} resolves dice ${dice}'), [
            'playerId' => $playerId,
            'player_name' => $this->game->getPlayerNameById($playerId),
            'dice' => $diceStr,
        ]);

        $diceCounts = $this->game->getRolledDiceCounts($playerId, $dice, false);

        $diceCountsWithoutAddedSmashes = $diceCounts; // clone

        $detail = $this->game->addSmashesFromCards($playerId, $diceCounts, $playerInTokyo);
        $diceAndCardsCounts = $diceCounts; // copy
        $diceAndCardsCounts[6] += $detail->addedSmashes;

        if ($detail->addedSmashes > 0) {
            $diceStr = '';
            for ($i=0; $i<$detail->addedSmashes; $i++) { 
                $diceStr .= $this->game->getDieFaceLogName(6, 0); 
            }
            
            $cardNamesStr = implode(', ', $detail->cardsAddingSmashes);

            $this->notify->all("resolvePlayerDiceAddedDice", clienttranslate('${player_name} adds ${dice} with ${card_name}'), [
                'playerId' => $playerId,
                'player_name' => $this->game->getPlayerNameById($playerId),
                'dice' => $diceStr,
                'card_name' => $cardNamesStr,
            ]);
        }

        // detritivore
        if ($diceCounts[1] >= 1 && $diceCounts[2] >= 1 && $diceCounts[3] >= 1) {
            $countDetritivore = $this->game->countCardOfType($playerId, DETRITIVORE_CARD);
            if ($countDetritivore > 0) {
                $this->game->applyGetPoints($playerId, 2 * $countDetritivore, DETRITIVORE_CARD);
            }

            // complete destruction
            $rolledFaces = $this->game->getRolledDiceFaces($playerId, $dice, false);
            if (
                $rolledFaces[41] >= 1 
                && $rolledFaces[51] >= 1 
                && ($rolledFaces[61] >= 1 || $detail->addedSmashes > 0)
            ) { // dice 1-2-3 check with previous if
                $countCompleteDestruction = $this->game->countCardOfType($playerId, COMPLETE_DESTRUCTION_CARD);
                if ($countCompleteDestruction > 0) {
                    $this->game->applyGetPoints($playerId, 9 * $countCompleteDestruction, COMPLETE_DESTRUCTION_CARD);
                }

                if ($this->game->powerUpExpansion->isActive()) {
                    $countPandaExpress = $this->game->countEvolutionOfType($playerId, PANDA_EXPRESS_EVOLUTION);
                    if ($countPandaExpress > 0) {
                        $this->game->applyGetPoints($playerId, 2 * $countPandaExpress, 3000 + PANDA_EXPRESS_EVOLUTION);
                        if ($this->game->mindbugExpansion->canGetExtraTurn()) {
                            $this->game->setGameStateValue(PANDA_EXPRESS_EXTRA_TURN, 1);
                        }
                    }
                }
            }
        }

        $fireBreathingDamages = [];
        // fire breathing
        if ($diceAndCardsCounts[6] >= 1) {
            $countFireBreathing = $this->game->countCardOfType($playerId, FIRE_BREATHING_CARD);
            if ($countFireBreathing > 0) {
                $playersIds = $this->game->getPlayersIds();
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

        if ($diceCounts[1] >= 4 && $this->game->kingKongExpansion->isActive() && $this->game->inTokyo($playerId) && $this->game->canUseSymbol($playerId, 1) && $this->game->canUseFace($playerId, 1)) {
            $this->game->kingKongExpansion->getNewTokyoTowerLevel($playerId);
        }
        
        $isCthulhuExpansion = $this->game->cthulhuExpansion->isActive();
        $fourOfAKind = false;
        $fourOfAKindWithCards = false;
        $flamingAuraDamages = [];
        for ($diceFace = 1; $diceFace <= 6; $diceFace++) {
            $canUseSymbolAndFace = $this->game->canUseSymbol($playerId, $diceFace) && $this->game->canUseFace($playerId, $diceFace);
            if ($diceAndCardsCounts[$diceFace] >= 4 && $canUseSymbolAndFace) {
                $fourOfAKindWithCards = true;
                if ($isCthulhuExpansion) {
                    $this->game->cthulhuExpansion->applyGetCultist($playerId, $diceFace);
                }
            }
            if ($diceCounts[$diceFace] >= 4 && $canUseSymbolAndFace) {
                $fourOfAKind = true;

                $countDrainingRay = $this->game->countCardOfType($playerId, DRAINING_RAY_CARD);
                if ($countDrainingRay > 0) {
                    $playersIds = $this->game->getPlayersIdsWithMaxColumn('player_score');
                    foreach ($playersIds as $pId) {
                        if ($pId != $playerId) {
                            $this->game->applyLosePoints($pId, $countDrainingRay, DRAINING_RAY_CARD);
                            $this->game->applyGetPoints($playerId, $countDrainingRay, DRAINING_RAY_CARD);
                        }
                    }
                }

                $countFlamingAura = $this->game->countCardOfType($playerId, FLAMING_AURA_CARD);
                if ($countFlamingAura > 0) {
                    $otherPlayersIds = $this->game->getOtherPlayersIds($playerId);
                    foreach ($otherPlayersIds as $otherPlayerId) {
                        $flamingAuraDamages[$otherPlayerId] = $countFlamingAura;
                    }
                }
            }
        }
        if ($isPowerUpExpansion && $fourOfAKindWithCards) {
            $count8thWonderOfTheWorld = $this->game->countEvolutionOfType($playerId, EIGHTH_WONDER_OF_THE_WORLD_EVOLUTION);
            if ($count8thWonderOfTheWorld > 0) {
                $this->game->applyGetPoints($playerId, $count8thWonderOfTheWorld, 3000 + EIGHTH_WONDER_OF_THE_WORLD_EVOLUTION);
            }
        }
        
        $funnyLookingButDangerousDamages = [];
        if ($isPowerUpExpansion) {
            if ($diceCounts[4] >= 3) {
                $countPandarwinism = $this->game->countEvolutionOfType($playerId, PANDARWINISM_EVOLUTION);
                if ($countPandarwinism > 0) {
                    $this->game->applyGetPoints($playerId, ($diceCounts[4] - 2) * $countPandarwinism, 3000 + PANDARWINISM_EVOLUTION);
                }
            }

            if ($diceCounts[2] >= 3) {
                $countFunnyLookingButDangerous = $this->game->countEvolutionOfType($playerId, FUNNY_LOOKING_BUT_DANGEROUS_EVOLUTION);
                if ($countFunnyLookingButDangerous > 0) {
                    $otherPlayersIds = $this->game->getOtherPlayersIds($playerId);
                    foreach ($otherPlayersIds as $otherPlayerId) {
                        $funnyLookingButDangerousDamages[$otherPlayerId] = $countFunnyLookingButDangerous;
                    }
                }
            }

            if ($diceCountsWithoutAddedSmashes[6] >= 1) {
                $energySwordEvolutions = $this->game->getEvolutionsOfType($playerId, ENERGY_SWORD_EVOLUTION);
                $countEnergySword = count(array_filter($energySwordEvolutions, fn($evolution) => $evolution->tokens > 0));
                if ($countEnergySword > 0) {
                    $this->game->applyGetEnergy($playerId, $diceCountsWithoutAddedSmashes[6] * $countEnergySword, 3000 + ENERGY_SWORD_EVOLUTION);
                }
            }
        }

        $threeTimesAsStrongEvolutions = $this->game->powerUpExpansion->evolutionCards->getPlayerVirtualByType($playerId, \THREE_TIMES_AS_STRONG_EVOLUTION, true, false);
        foreach ($threeTimesAsStrongEvolutions as $threeTimesAsStrongEvolution) {
            /** @disregard */
            $threeTimesAsStrongEvolution->applyEffect(new Context($this, $playerId));
        }

        $this->game->setGlobalVariable(FIRE_BREATHING_DAMAGES, $fireBreathingDamages);
        $this->game->setGlobalVariable(FUNNY_LOOKING_BUT_DANGEROUS_DAMAGES, $funnyLookingButDangerousDamages);
        $this->game->setGlobalVariable(FLAMING_AURA_DAMAGES, $flamingAuraDamages);
        $this->game->setGlobalVariable(DICE_COUNTS, $diceAndCardsCounts);
    }

    function canLeaveHibernation(int $playerId, $dice = null) {
        $countHibernation = $this->game->countCardOfType($playerId, HIBERNATION_CARD);
        if ($countHibernation == 0) {
            return false;
        }

        if ($dice == null) {
            $dice = $this->game->getPlayerRolledDice($playerId, true, true, false);
        }

        $diceCounts = $this->game->getRolledDiceCounts($playerId, $dice, false);

        foreach($diceCounts as $face => $number) {
            if ($face !== 4 && $face !== 5 && $number > 0) {
                return true;
            }
        }

        return false;
    }
}
