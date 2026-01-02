<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\Game;

use const Bga\Games\KingOfTokyo\PowerCards\FRENZY;
use const Bga\Games\KingOfTokyo\PowerCards\MINDBUG_KEYWORDS_END_TURN;

class BeforeEndTurnMulti extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_MULTIPLAYER_BEFORE_END_TURN,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            name: 'beforeEndTurn',
            transitions: [
                'next' => \ST_AFTER_BEFORE_END_TURN,
            ],
        );
    }

    public function getArgs(int $activePlayerId): array {
        $isPowerUpExpansion = $this->game->powerUpExpansion->isActive();

        $highlighted = [];
        $privatePlayers = [];
        $couldPlayEvolution = false;

        $frenzyAlreadyActivated = count($this->game->mindbugExpansion->getActivatedCards($activePlayerId, FRENZY)) > 0;
        $consumableCards = [];
        $consumableEvolutions = [];
        if (!$frenzyAlreadyActivated) {
            $consumableCards = $this->game->mindbugExpansion->getConsumableCards($activePlayerId, MINDBUG_KEYWORDS_END_TURN);
            $consumableEvolutions = $this->game->mindbugExpansion->getConsumableEvolutions($activePlayerId, MINDBUG_KEYWORDS_END_TURN);
        }
        $canPlayConsumable = count($consumableCards) > 0 || count($consumableEvolutions) > 0;
        
        if ($isPowerUpExpansion) {
            $highlighted = $this->game->powerUpExpansion->getHighlightedEvolutions($this->game->EVOLUTION_TO_PLAY_BEFORE_END);

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

            $couldPlayEvolution = count($this->game->getPlayersIdsWhoCouldPlayEvolutions([$activePlayerId], $this->game->EVOLUTION_TO_PLAY_BEFORE_END)) > 0;
        } 

        return [
            'highlighted' => $highlighted,
            '_private' => $privatePlayers,
            'consumableCards' => $consumableCards,
            'consumableEvolutions' => $consumableEvolutions,
            'canPlayConsumable' => $canPlayConsumable,
            'couldPlayEvolution' => $couldPlayEvolution,
            '_no_notify' => !$canPlayConsumable && !$couldPlayEvolution,
        ];
    }

    public function onEnteringState(int $activePlayerId, array $args) {
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

        if ($args['_no_notify']) {
            return \ST_AFTER_BEFORE_END_TURN;
        }

        $playersToactivate = $playersWithPotentialEvolution;
        if ($args['canPlayConsumable'] && !in_array($activePlayerId, $playersToactivate)) {
            $playersToactivate[] = $activePlayerId;
        }

        $this->gamestate->setPlayersMultiactive($playersToactivate, 'next', true);
    }

    #[PossibleAction]
    public function actActivateConsumable(int $id, string $keyword, int $activePlayerId) {
        $this->game->mindbugExpansion->activateConsumable($id, $keyword, $activePlayerId, MINDBUG_KEYWORDS_END_TURN);
        return self::class;
    }

    #[PossibleAction]
    public function actActivateConsumableEvolution(int $id, string $keyword, int $activePlayerId) {
        $this->game->mindbugExpansion->activateConsumableEvolution($id, $keyword, $activePlayerId, MINDBUG_KEYWORDS_END_TURN);
        return self::class;
    }

    #[PossibleAction]
    public function actSkipBeforeEndTurn(int $currentPlayerId) {
        $this->gamestate->setPlayerNonMultiactive($currentPlayerId, 'next');
    }

    public function zombie(int $playerId) {
        return $this->actSkipBeforeEndTurn($playerId);
    }
}

