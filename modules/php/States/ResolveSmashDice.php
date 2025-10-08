<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class ResolveSmashDice extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_RESOLVE_SMASH_DICE,
            type: StateType::GAME,
            name: 'resolveSmashDice',
        );
    }

    public function onEnteringState(int $activePlayerId) {
        $this->game->resolveSmashDiceState();
    }
}

