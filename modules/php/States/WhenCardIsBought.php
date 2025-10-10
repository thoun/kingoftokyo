<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class WhenCardIsBought extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_MULTIPLAYER_WHEN_CARD_IS_BOUGHT,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            name: 'cardIsBought',
            description: clienttranslate('Some players may activate an Evolution card'),
            descriptionMyTurn: clienttranslate('${you} may activate an Evolution card'),
            transitions: [
                'next' => \ST_AFTER_WHEN_CARD_IS_BOUGHT,
            ],
        );
    }

    public function getArgs(int $activePlayerId): array {
        $isPowerUpExpansion = $this->game->powerUpExpansion->isActive();

        $highlighted = $isPowerUpExpansion ? $this->game->getHighlightedEvolutions($this->game->EVOLUTION_TO_PLAY_WHEN_CARD_IS_BOUGHT) : [];

        return [
            'highlighted' => $highlighted,
        ];
    }

    public function onEnteringState(int $activePlayerId, array $args) {
        $otherPlayersIds = $this->game->getOtherPlayersIds($activePlayerId);
        $playersWithPotentialEvolution = $this->game->getPlayersIdsWhoCouldPlayEvolutions(
            $otherPlayersIds,
            $this->game->EVOLUTION_TO_PLAY_WHEN_CARD_IS_BOUGHT,
        );

        if (empty($playersWithPotentialEvolution)) {
            return \ST_AFTER_WHEN_CARD_IS_BOUGHT;
        }

        $this->gamestate->setPlayersMultiactive($playersWithPotentialEvolution, 'next', true);
    }

    #[PossibleAction]
    function actSkipCardIsBought(int $currentPlayerId) {
        $this->game->gamestate->setPlayerNonMultiactive($currentPlayerId, 'next');
    }

    public function zombie(int $playerId) {
        return $this->actSkipCardIsBought($playerId);
    }
}
