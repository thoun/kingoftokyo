<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class ResolveHeartDice extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_RESOLVE_HEART_DICE,
            type: StateType::GAME,
        );
    }

    public function onEnteringState(int $activePlayerId) {
        $diceCounts = $this->game->getGlobalVariable(\DICE_COUNTS, true);

        $diceCount = $this->game->addHighTideDice($activePlayerId, $diceCounts[4]);

        if ($diceCount > 0) {
            $this->game->resolveHealthDice($activePlayerId, $diceCount);
        } else {
            if ($this->game->powerUpExpansion->isActive()) {
                $countGrowingFast = $this->game->countEvolutionOfType($activePlayerId, \GROWING_FAST_EVOLUTION);
                if ($countGrowingFast > 0) {
                    $this->game->applyGetHealth($activePlayerId, $countGrowingFast, 3000 + \GROWING_FAST_EVOLUTION, $activePlayerId);
                }
            }
        }

        return ResolveEnergyDice::class;
    }
}

