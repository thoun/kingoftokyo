<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\Game;

class BeforeEndTurnMulti extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_MULTIPLAYER_BEFORE_END_TURN,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            name: 'beforeEndTurn',
            description: clienttranslate('${actplayer} may activate an Evolution card'),
            descriptionMyTurn: clienttranslate('${you} may activate an Evolution card'),
            transitions: [
                'next' => \ST_AFTER_BEFORE_END_TURN,
                'end' => \ST_AFTER_BEFORE_END_TURN,
            ],
        );
    }

    public function getArgs(): array {
        $isPowerUpExpansion = $this->game->powerUpExpansion->isActive();

        $highlighted = [];
        $privatePlayers = [];
        
        if ($isPowerUpExpansion) {
            $highlighted = $this->game->getHighlightedEvolutions($this->game->EVOLUTION_TO_PLAY_BEFORE_END);

            $players = $this->game->getPlayers();
            foreach ($players as $player) {
                $evolutionsWithEffectCounter = [];
                $handEvolutions = $this->game->getEvolutionCardsByLocation('hand', $player->id);
                // all $this->EVOLUTION_TO_PLAY_BEFORE_END_MULTI = [
                $angerBatteriesEvolution = Arrays::find($handEvolutions, fn($evolution) => $evolution->type == ANGER_BATTERIES_EVOLUTION);
                $strokeOfGeniusEvolution = Arrays::find($handEvolutions, fn($evolution) => $evolution->type == STROKE_OF_GENIUS_EVOLUTION);
                $cultWorshippersEvolution = Arrays::find($handEvolutions, fn($evolution) => $evolution->type == CULT_WORSHIPPERS_EVOLUTION);

                if ($angerBatteriesEvolution != null) {
                    $evolutionsWithEffectCounter[$angerBatteriesEvolution->id] = [$player->turnLostHealth, 5];
                }
                if ($strokeOfGeniusEvolution != null) {
                    $evolutionsWithEffectCounter[$strokeOfGeniusEvolution->id] = [$player->turnEnergy, 5];
                }
                if ($cultWorshippersEvolution != null) {
                    $evolutionsWithEffectCounter[$cultWorshippersEvolution->id] = [$player->turnGainedHealth, 0];
                }
                $privatePlayers[$player->id] = $evolutionsWithEffectCounter;
            }
        } 

        return [
            'highlighted' => $highlighted,
            '_private' => $privatePlayers,
        ];
    }

    public function onEnteringState(int $activePlayerId): ?int {
        $this->game->removeDiscardCards($activePlayerId);

        $playersIds = $this->game->getPlayersIds();
        $playersWithPotentialEvolution = $this->game->getPlayersIdsWhoCouldPlayEvolutions(
            $playersIds,
            $this->game->EVOLUTION_TO_PLAY_BEFORE_END_MULTI,
        );

        $activePlayersWithPotentialEvolution = $this->game->getPlayersIdsWhoCouldPlayEvolutions(
            [$activePlayerId],
            $this->game->EVOLUTION_TO_PLAY_BEFORE_END_ACTIVE,
        );

        $activePlayersWithPotentialEvolution = array_values(
            array_filter($activePlayersWithPotentialEvolution, fn($pId) => $pId === $activePlayerId)
        );

        if (!empty($activePlayersWithPotentialEvolution) && !in_array($activePlayerId, $playersWithPotentialEvolution, true)) {
            $playersWithPotentialEvolution[] = $activePlayerId;
        }

        $playersWithPotentialEvolution = array_values(array_unique($playersWithPotentialEvolution));

        if (empty($playersWithPotentialEvolution)) {
            return \ST_AFTER_BEFORE_END_TURN;
        }

        $this->gamestate->setPlayersMultiactive($playersWithPotentialEvolution, 'next', true);
        return null;
    }

    #[PossibleAction]
    public function actSkipBeforeEndTurn(int $currentPlayerId): void {
        $this->gamestate->setPlayerNonMultiactive($currentPlayerId, 'next');
    }

    public function zombie(int $playerId): void {
        $this->actSkipBeforeEndTurn($playerId);
    }
}

