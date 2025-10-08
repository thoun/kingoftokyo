<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class InitialDiceRoll extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_INITIAL_DICE_ROLL,
            type: StateType::GAME,
            name: 'initialDiceRoll',
            transitions: [
                'throw' => \ST_PLAYER_THROW_DICE,
                'buyCard' => \ST_PLAYER_BUY_CARD,
            ],
        );
    }

    public function onEnteringState(int $activePlayerId) {
        if ($this->game->getPlayer($activePlayerId)->eliminated) {
            return \ST_PLAYER_BUY_CARD;
        }

        $this->game->setGameStateValue(\DICE_NUMBER, $this->game->getDiceNumber($activePlayerId, true));
        $this->game->throwDice($activePlayerId, true);

        if ($this->game->isMutantEvolutionVariant()) {
            $isBeastForm = $this->game->isBeastForm($activePlayerId);
            $this->game->incStat(1, $isBeastForm ? 'turnsInBeastForm' : 'turnsInBipedForm', $activePlayerId);
        }

        return \ST_PLAYER_THROW_DICE;
    }
}

