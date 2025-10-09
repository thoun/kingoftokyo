<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\Game;

class ChooseEvolutionCard extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_CHOOSE_EVOLUTION_CARD,
            type: StateType::ACTIVE_PLAYER,
            name: 'chooseEvolutionCard',
            description: clienttranslate('${actplayer} must choose an Evolution card'),
            descriptionMyTurn: clienttranslate('${you} must choose an Evolution card'),
        );
    }

    public function getArgs(int $activePlayerId): array {
        return [
            '_private' => [
                $activePlayerId => [
                    'evolutions' => $this->game->pickEvolutionCards($activePlayerId),
                ],
            ],
        ];
    }

    #[PossibleAction]
    public function actChooseEvolutionCard(int $id, int $activePlayerId) {
        $this->game->applyChooseEvolutionCard($activePlayerId, $id, false);

        $nextState = intval($this->game->getGameStateValue(STATE_AFTER_RESOLVE));
        $this->gamestate->jumpToState($nextState);
    }

    public function zombie(int $playerId) {
        $cards = $this->game->pickEvolutionCards($playerId);
        if (!empty($cards)) {
            $zombieChoice = $this->getRandomZombieChoice(Arrays::pluck($cards, 'id'));
            return $this->actChooseEvolutionCard($zombieChoice, $playerId);
        }

        $nextState = (int)$this->game->getGameStateValue(\STATE_AFTER_RESOLVE);
        $this->game->gamestate->jumpToState($nextState);
    }
}
