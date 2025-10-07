<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

class StartTurn extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_START_TURN,
            type: StateType::GAME,
        );
    }

    public function onEnteringState(int $activePlayerId) {
        $idsInTokyo = $this->game->getPlayersIdsInTokyo();
        foreach($idsInTokyo as $id) {
            $this->game->incStat(1, 'turnsInTokyo', $id);
        }

        $this->game->incStat(1, 'turnsNumber');
        $this->game->incStat(1, 'turnsNumber', $activePlayerId);

        $activePlayerInTokyo = $this->game->inTokyo($activePlayerId);

        $batteryMonsterCards = $this->game->getCardsOfType($activePlayerId, \BATTERY_MONSTER_CARD);
        foreach($batteryMonsterCards as $batteryMonsterCard) {
            $this->game->applyBatteryMonster($activePlayerId, $batteryMonsterCard);
        }

        $princessCards = $this->game->getCardsOfType($activePlayerId, \PRINCESS_CARD);
        foreach($princessCards as $princessCard) {
            $this->game->applyGetPoints($activePlayerId, 1, \PRINCESS_CARD);
        }

        if ($this->game->getPlayerHealth($activePlayerId) < 3) {
            $countNanobots = $this->game->countCardOfType($activePlayerId, \NANOBOTS_CARD);
            if ($countNanobots > 0) {
                $this->game->applyGetHealth($activePlayerId, 2 * $countNanobots, \NANOBOTS_CARD, $activePlayerId);
            }
        }

        if ($this->game->wickednessExpansion->isActive()) {
            $this->game->wickednessTiles->onStartTurn(new Context(
                $this->game,
                currentPlayerId: $activePlayerId,
                currentPlayerInTokyo: $activePlayerInTokyo
            ));
        }

        if ($this->game->kingKongExpansion->isActive()) {
            $this->game->kingKongExpansion->onPlayerStartTurn($activePlayerId);
        }

        if ($this->game->cybertoothExpansion->isActive()) {
            $this->game->cybertoothExpansion->onPlayerStartTurn($activePlayerId);
        }

        if ($this->game->powerUpExpansion->isActive()) {
            $coldWaveCards = $this->game->getEvolutionsOfType($activePlayerId, \COLD_WAVE_EVOLUTION);
            if (count($coldWaveCards) > 0) {
                $this->game->removeEvolutions($activePlayerId, $coldWaveCards);
            }

            $mothershipEvolutionCards = $this->game->getEvolutionsOfType($activePlayerId, \MOTHERSHIP_SUPPORT_EVOLUTION);
            if (count($mothershipEvolutionCards) > 0) {
                $this->notify->player($activePlayerId, 'toggleMothershipSupportUsed', '', [
                    'playerId' => $activePlayerId,
                    'used' => false,
                ]);
            }

            $encasedInIceCards = $this->game->getEvolutionsOfType($activePlayerId, \ENCASED_IN_ICE_EVOLUTION);
            if (count($encasedInIceCards) > 0 && intval($this->game->getGameStateValue(\ENCASED_IN_ICE_DIE_ID)) > 0) {
                $this->game->setGameStateValue(\ENCASED_IN_ICE_DIE_ID, 0);
            }
        }

        if ($activePlayerInTokyo) {
            if ($this->game->isTwoPlayersVariant()) {
                $playerGettingEnergy = $this->game->getPlayerGettingEnergyOrHeart($activePlayerId);

                if ($this->game->canGainEnergy($playerGettingEnergy)) {
                    $incEnergy = 1;

                    if ($activePlayerId == $playerGettingEnergy) {
                        $this->notify->all('log', clienttranslate('${player_name} starts turn in Tokyo and gains ${deltaEnergy} [Energy]'), [
                            'playerId' => $activePlayerId,
                            'player_name' => $this->game->getPlayerNameById($activePlayerId),
                            'deltaEnergy' => $incEnergy,
                        ]);
                    }
                    $this->game->applyGetEnergy($playerGettingEnergy, $incEnergy, 0);
                }
            } else {
                if ($this->game->canGainPoints($activePlayerId) === null) {
                    $incScore = 2;
                    $this->game->applyGetPoints($activePlayerId, $incScore, -1);

                    $this->notify->all('points', clienttranslate('${player_name} starts turn in Tokyo and gains ${deltaPoints} [Star]'), [
                        'playerId' => $activePlayerId,
                        'player_name' => $this->game->getPlayerNameById($activePlayerId),
                        'points' => $this->game->getPlayerScore($activePlayerId),
                        'deltaPoints' => $incScore,
                    ]);
                }
            }

            $countUrbavore = $this->game->countCardOfType($activePlayerId, \URBAVORE_CARD);
            if ($countUrbavore > 0) {
                $this->game->applyGetPoints($activePlayerId, $countUrbavore, \URBAVORE_CARD);
            }

            if ($this->game->powerUpExpansion->isActive()) {
                $countIAmTheKing = $this->game->countEvolutionOfType($activePlayerId, \I_AM_THE_KING_EVOLUTION);
                if ($countIAmTheKing > 0) {
                    $this->game->applyGetPoints($activePlayerId, $countIAmTheKing, 3000 + \I_AM_THE_KING_EVOLUTION);
                }
                $countDefenderOfTokyo = $this->game->countEvolutionOfType($activePlayerId, \DEFENDER_OF_TOKYO_EVOLUTION);
                if ($countDefenderOfTokyo > 0) {
                    $otherPlayersIds = $this->game->getOtherPlayersIds($activePlayerId);
                    foreach ($otherPlayersIds as $otherPlayerId) {
                        $this->game->applyLosePoints($otherPlayerId, $countDefenderOfTokyo, 3000 + \DEFENDER_OF_TOKYO_EVOLUTION);
                    }
                }
            }
        }

        $damages = [];
        if ($this->game->anubisExpansion->isActive()) {
            $curseCardType = $this->game->anubisExpansion->getCurseCardType();
            $logCardType = 1000 + $curseCardType;
            switch($curseCardType) {
                case \SET_S_STORM_CURSE_CARD:
                    $damages[] = new Damage($activePlayerId, 1, 0, $logCardType);
                    break;
                case \BUILDERS_UPRISING_CURSE_CARD:
                    $this->game->applyLosePoints($activePlayerId, 2, $logCardType);
                    break;
                case \ORDEAL_OF_THE_MIGHTY_CURSE_CARD:
                    $playersIds = $this->game->getPlayersIdsWithMaxColumn('player_health');
                    foreach ($playersIds as $pId) {
                        $damages[] = new Damage($pId, 1, 0, $logCardType);
                    }
                    break;
                case \ORDEAL_OF_THE_WEALTHY_CURSE_CARD:
                    $playersIds = $this->game->getPlayersIdsWithMaxColumn('player_score');
                    foreach ($playersIds as $pId) {
                        $this->game->applyLosePoints($pId, 1, $logCardType);
                    }
                    break;
                case \ORDEAL_OF_THE_SPIRITUAL_CURSE_CARD:
                    $playersIds = $this->game->getPlayersIdsWithMaxColumn('player_energy');
                    foreach ($playersIds as $pId) {
                        $this->game->applyLoseEnergy($pId, 1, $logCardType);
                    }
                    break;
            }
        }

        $this->game->startTurnInitDice();

        $redirectAfterStartTurn = $this->game->redirectAfterStartTurn($activePlayerId);

        $this->game->goToState($redirectAfterStartTurn, $damages);
    }
}
