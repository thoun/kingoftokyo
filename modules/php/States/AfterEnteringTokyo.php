<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class AfterEnteringTokyo extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_PLAYER_AFTER_ENTERING_TOKYO,
            type: StateType::ACTIVE_PLAYER,
            name: 'afterEnteringTokyo',
            description: clienttranslate('Some players may activate an Evolution card'),
            descriptionMyTurn: clienttranslate('${you} may activate an Evolution card'),
        );
    }

    public function getArgs(int $activePlayerId): array {
        $player = $this->game->getPlayer($activePlayerId);

        $highlighted = $player->location > 0 && $player->turnEnteredTokyo
            ? $this->game->powerUpExpansion->getHighlightedEvolutions($this->game->EVOLUTION_TO_PLAY_AFTER_ENTERING_TOKYO)
            : $this->game->powerUpExpansion->getHighlightedEvolutions($this->game->EVOLUTION_TO_PLAY_AFTER_NOT_ENTERING_TOKYO);

        return [
            'highlighted' => $highlighted,
            'noExtraTurnWarning' => $this->game->mindbugExpansion->canGetExtraTurn() ? [] : [\JUNGLE_FRENZY_EVOLUTION],
        ];
    }

    public function onEnteringState(int $activePlayerId) {
        $player = $this->game->getPlayer($activePlayerId);

        $couldPlay = $this->game->getPlayersIdsWhoCouldPlayEvolutions(
            [$activePlayerId],
            $player->location == 0 || !$player->turnEnteredTokyo
                ? $this->game->EVOLUTION_TO_PLAY_AFTER_NOT_ENTERING_TOKYO
                : $this->game->EVOLUTION_TO_PLAY_AFTER_ENTERING_TOKYO,
        );

        if (empty($couldPlay)) {
            return $this->game->redirectAfterEnterTokyo($activePlayerId);
        }
    }

    #[PossibleAction]
    public function actSkipAfterEnteringTokyo(int $currentPlayerId) {
        return $this->game->redirectAfterEnterTokyo($currentPlayerId);
    }

    public function zombie(int $playerId) {
        return $this->actSkipAfterEnteringTokyo($playerId);
    }
}
