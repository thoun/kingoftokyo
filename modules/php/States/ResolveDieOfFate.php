<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class ResolveDieOfFate extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_RESOLVE_DIE_OF_FATE,
            type: StateType::GAME,
            transitions: [
                'next' => \ST_RESOLVE_DICE,
                'reroll' => \ST_MULTIPLAYER_REROLL_DICE,
            ],
        );
    }

    public function onEnteringState(int $activePlayerId) {
        if (
            !$this->game->anubisExpansion->isActive()
            || intval($this->game->getGameStateValue(\BUILDERS_UPRISING_EXTRA_TURN)) === 2
        ) {
            return \ST_RESOLVE_DICE;
        }

        $damagesOrState = $this->game->anubisExpansion->resolveDieOfFate($activePlayerId);

        if (is_int($damagesOrState)) {
            if ($damagesOrState !== -1) {
                return $damagesOrState;
            }
            return null;
        }

        $nextState = \ST_RESOLVE_DICE;

        $playerIdWithGoldenScarab = $this->game->anubisExpansion->getPlayerIdWithGoldenScarab();
        if (
            $this->game->anubisExpansion->getCurseCardType() === \CONFUSED_SENSES_CURSE_CARD
            && $playerIdWithGoldenScarab !== null
            && $activePlayerId !== $playerIdWithGoldenScarab
        ) {
            $nextState = \ST_MULTIPLAYER_REROLL_DICE;
        }

        $this->game->goToState($nextState, $damagesOrState);
    }
}

