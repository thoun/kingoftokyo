<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class ResolveEnergyDice extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_RESOLVE_ENERGY_DICE,
            type: StateType::GAME,
        );
    }

    public function onEnteringState(int $activePlayerId) {
        $diceCounts = $this->game->getGlobalVariable(\DICE_COUNTS, true);

        $diceCount = $diceCounts[5];
        if ($diceCount > 0) {
            $this->game->resolveEnergyDice($activePlayerId, $diceCount);
        }

        return $this->game->redirectAfterResolveEnergyDice();
    }
}

