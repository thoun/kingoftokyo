<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class ResolveNumberDice extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_RESOLVE_NUMBER_DICE,
            type: StateType::GAME,
            name: 'resolveNumberDice',
        );
    }

    public function getArgs(int $activePlayerId): array {
        return [
            'dice' => $this->game->getPlayerRolledDice($activePlayerId, true, true, false),
            'canHealWithDice' => $this->game->canHealWithDice($activePlayerId),
            'frozenFaces' => $this->game->frozenFaces($activePlayerId),
        ];
    }

    public function onEnteringState(int $activePlayerId) {
        if ($this->game->countCardOfType($activePlayerId, \HIBERNATION_CARD) > 0) {
            return $this->game->redirectAfterResolveNumberDice();
        }

        $diceCounts = $this->game->getGlobalVariable(\DICE_COUNTS, true);

        for ($diceFace = 1; $diceFace <= 3; $diceFace++) {
            $diceCount = $diceCounts[$diceFace];
            $redirected = $this->game->resolveNumberDice($activePlayerId, $diceFace, $diceCount);
            if ($redirected) {
                return null;
            }
        }

        return $this->game->redirectAfterResolveNumberDice();
    }
}

