<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\Actions\Types\IntParam;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;
use Bga\GameFrameworkPrototype\Helpers\Arrays;

class ChooseInitialCard extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_PLAYER_CHOOSE_INITIAL_CARD,
            type: StateType::ACTIVE_PLAYER,
            name: 'chooseInitialCard',
        );
    }

    public function getArgs(int $activePlayerId): array {
        $chooseCostume = $this->game->isHalloweenExpansion();
        $chooseEvolution = $this->game->powerUpExpansion->isActive();

        $args = [
            'chooseCostume' => $chooseCostume,
            'chooseEvolution' => $chooseEvolution,
        ];

        if ($chooseCostume) {
            $args['cards'] = $this->game->powerCards->getCardsOnTop(2, 'costumedeck');
        }

        if ($chooseEvolution) {
            $args['_private'] = [
                $activePlayerId => [
                    'evolutions' => $this->game->pickEvolutionCards($activePlayerId),
                ]
            ];
        }

        return $args;
    }

    public function onEnteringState(int $activePlayerId) {
        if ($this->game->isInitialCardDistributionComplete()) {
            return StartGame::class;
        }
    }

    #[PossibleAction]
    public function actChooseInitialCard(#[IntParam(name: 'id')] ?int $costumeId, ?int $evolutionId, int $currentPlayerId, array $args) {
        if (!empty($args['chooseCostume'])) {
            if ($costumeId === null || $costumeId === 0) {
                throw new \BgaUserException('No selected Costume card');
            }

            $topCards = $this->game->powerCards->getCardsOnTop(2, 'costumedeck');
            if (!Arrays::some($topCards, fn($topCard) => $topCard->id == $costumeId)) {
                throw new \BgaUserException('Card not available');
            }
            $otherCard = Arrays::find($topCards, fn($topCard) => $topCard->id != $costumeId);

            $this->game->setInitialCostumeCard($currentPlayerId, $costumeId, $otherCard);
        }

        if (!empty($args['chooseEvolution'])) {
            if ($evolutionId === null || $evolutionId === 0) {
                throw new \BgaUserException('No selected Evolution card');
            }

            $this->game->applyChooseEvolutionCard($currentPlayerId, $evolutionId, true);
        }

        return ChooseInitialCardNextPlayer::class;
    }

    public function zombie(int $playerId, array $args) {
        $costumeId = null;
        if (!empty($args['chooseCostume']) && !empty($args['cards'])) {
            $costumeId = $this->getRandomZombieChoice($args['cards'])->id ?? null;
        }

        $evolutionId = null;
        if (!empty($args['chooseEvolution']) && isset($args['_private'][$playerId]['evolutions'])) {
            $evolutions = $args['_private'][$playerId]['evolutions'];
            if (count($evolutions) > 0) {
                $evolutionId = $this->getRandomZombieChoice($evolutions)->id;
            }
        }

        return $this->actChooseInitialCard($costumeId, $evolutionId, $playerId, $args);
    }
}

