<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\Game;

class DuringResolveDice extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_PLAYER_DURING_RESOLVE_DICE,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            name: 'beforeResolveDice',
            description: clienttranslate('Some players may activate an Evolution card'),
            descriptionMyTurn: clienttranslate('${you} may activate an Evolution card'),
        );
    }

    public function getArgs(int $activePlayerId): array {
        $isPowerUpExpansion = $this->game->powerUpExpansion->isActive();

        $highlighted = [];
        if ($isPowerUpExpansion) {
            $highlighted = $this->game->powerUpExpansion->getHighlightedEvolutions($this->game->EVOLUTION_TO_PLAY_DURING_RESOLVE_DICE);
        }

        return [
            'dice' => $this->game->getPlayerRolledDice($activePlayerId, true, true, false),
            'canHealWithDice' => $this->game->canHealWithDice($activePlayerId),
            'frozenFaces' => $this->game->frozenFaces($activePlayerId),

            'highlighted' => $highlighted,
            '_no_notify' => !$isPowerUpExpansion,
        ];
    }

    public function onEnteringState(int $activePlayerId, array $args) {
        if ($args['_no_notify']) {
            return ResolveNumberDice::class;
        }

        $this->game->removeDiscardCards($activePlayerId);

        $playersIds = $this->game->getPlayersIds();
        $otherPlayersIds = Arrays::diff($playersIds, [$activePlayerId]);
        $playersWithPotentialEvolution = [];
        $playersWithPotentialEvolution = $this->game->getPlayersIdsWhoCouldPlayEvolutions(
            $otherPlayersIds,
            $this->game->EVOLUTION_TO_PLAY_DURING_RESOLVE_DICE_MULTI_OTHERS,
        );

        $activePlayersWithPotentialEvolution = $this->game->getPlayersIdsWhoCouldPlayEvolutions(
            [$activePlayerId],
            $this->game->EVOLUTION_TO_PLAY_DURING_RESOLVE_DICE_ACTIVE,
        );

        $activePlayersWithPotentialEvolution = array_values(
            array_filter($activePlayersWithPotentialEvolution, fn($pId) => $pId === $activePlayerId)
        );

        if (!empty($activePlayersWithPotentialEvolution) && !in_array($activePlayerId, $playersWithPotentialEvolution, true)) {
            $playersWithPotentialEvolution[] = $activePlayerId;
        }

        $playersWithPotentialEvolution = array_values(array_unique($playersWithPotentialEvolution));

        $playersToactivate = $playersWithPotentialEvolution;

        $this->gamestate->setPlayersMultiactive($playersToactivate, ResolveNumberDice::class, true);
    }

    #[PossibleAction]
    public function actSkipBeforeResolveDice(int $currentPlayerId) {
        $this->gamestate->setPlayerNonMultiactive($currentPlayerId, ResolveNumberDice::class);
    }

    public function zombie(int $playerId) {
        return $this->actSkipBeforeResolveDice($playerId);
    }
}

