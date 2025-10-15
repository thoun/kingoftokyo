<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class BeforeResolveDice extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_PLAYER_BEFORE_RESOLVE_DICE,
            type: StateType::ACTIVE_PLAYER,
            name: 'beforeResolveDice',
            description: clienttranslate('${actplayer} may activate an Evolution card'),
            descriptionMyTurn: clienttranslate('${you} may activate an Evolution card'),
        );
    }

    public function getArgs(): array {
        $isPowerUpExpansion = $this->game->powerUpExpansion->isActive();

        $highlighted = $isPowerUpExpansion ? $this->game->getHighlightedEvolutions($this->game->EVOLUTION_TO_PLAY_BEFORE_RESOLVE_DICE) : [];

        return [
            'highlighted' => $highlighted,
            '_no_notify' => !$isPowerUpExpansion,
        ];
    }

    public function onEnteringState(int $activePlayerId, array $args) {
        if ($args['_no_notify']) {
            return $this->game->redirectAfterBeforeResolveDice();
        }

        $couldPlay = $this->game->getPlayersIdsWhoCouldPlayEvolutions(
            [$activePlayerId],
            $this->game->EVOLUTION_TO_PLAY_BEFORE_RESOLVE_DICE,
        );

        if (empty($couldPlay)) {
            return $this->game->redirectAfterBeforeResolveDice();
        }

        return null;
    }

    #[PossibleAction]
    public function actSkipBeforeResolveDice() {
        return $this->game->redirectAfterBeforeResolveDice();
    }

    public function zombie(int $playerId) {
        return $this->actSkipBeforeResolveDice();
    }
}

