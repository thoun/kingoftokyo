<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\GameFrameworkPrototype\Helpers\Arrays;

class BeforeEnteringTokyo extends GameState {
    public function __construct(protected \Bga\Games\KingOfTokyo\Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_MULTIPLAYER_BEFORE_ENTERING_TOKYO,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            name: 'beforeEnteringTokyo',
            description: clienttranslate('Some players may activate an Evolution card'),
            descriptionMyTurn: clienttranslate('${you} may activate an Evolution card'),
            transitions: [
                'next' => \ST_ENTER_TOKYO,
            ],
        );
    }

    public function getArgs(int $activePlayerId): array {
        $isPowerUpExpansion = $this->game->powerUpExpansion->isActive();

        $highlighted = $isPowerUpExpansion && $this->game->tokyoHasFreeSpot() ? $this->game->getHighlightedEvolutions($this->game->EVOLUTION_TO_PLAY_BEFORE_ENTERING_TOKYO) : [];

        $otherPlayersIds = $this->game->getOtherPlayersIds($activePlayerId);

        $felineMotorPlayersIds = Arrays::filter($otherPlayersIds, fn($pId) => $this->game->countEvolutionOfType($pId, FELINE_MOTOR_EVOLUTION) > 0);

        return [
            'highlighted' => $highlighted,
            'canUseFelineMotor' => $felineMotorPlayersIds,
        ];
    }

    public function onEnteringState(int $activePlayerId) {
        if (!$this->game->powerUpExpansion->isActive() || !$this->game->tokyoHasFreeSpot()) {
            return $this->game->redirectAfterHalfMovePhase();
        }

        $otherPlayersIds = $this->game->getOtherPlayersIds($activePlayerId);
        $couldPlay = array_values(array_filter(
            $otherPlayersIds,
            fn($pId) => !empty($this->game->getPlayersIdsWhoCouldPlayEvolutions([$pId], $this->game->EVOLUTION_TO_PLAY_BEFORE_ENTERING_TOKYO))
        ));

        if ($this->game->getMimickedEvolutionType() == \FELINE_MOTOR_EVOLUTION) {
            $icyReflection = $this->game->getEvolutionCardsByType(\ICY_REFLECTION_EVOLUTION)[0] ?? null;
            if ($icyReflection !== null && in_array($icyReflection->location_arg, $otherPlayersIds, true)) {
                $couldPlay[] = $icyReflection->location_arg;
            }
        }

        if (!empty($couldPlay)) {
            $this->gamestate->setPlayersMultiactive(array_values(array_unique($couldPlay)), 'next', true);
            return null;
        }

        return $this->game->redirectAfterHalfMovePhase();
    }

    #[PossibleAction]
    public function actSkipBeforeEnteringTokyo(int $currentPlayerId): void {
        $this->gamestate->setPlayerNonMultiactive($currentPlayerId, 'next');
    }

    public function zombie(int $playerId): void {
        $this->actSkipBeforeEnteringTokyo($playerId);
    }
}
