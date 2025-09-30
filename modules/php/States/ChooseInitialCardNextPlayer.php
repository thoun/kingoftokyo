<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class ChooseInitialCardNextPlayer extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct($game,
            id: ST_CHOOSE_INITIAL_CARD_NEXT_PLAYER,
            type: StateType::GAME,
        );
    }

    function onEnteringState() {        
        $playerId = (int)$this->game->activeNextPlayer();
        $this->game->giveExtraTime($playerId);

        if ($this->game->isInitialCardDistributionComplete()) {
            return ST_START_GAME;
        } else {
            return ST_PLAYER_CHOOSE_INITIAL_CARD;
        }
    }
}
