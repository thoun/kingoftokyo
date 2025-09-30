<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class BeforeEndTurn extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct($game,
            id: ST_AFTER_BEFORE_END_TURN,
            type: StateType::GAME,
        );
    }

    function onEnteringState() { 
        $this->game->goToState($this->game->redirectAfterBeforeEndTurn());
    }
}
