<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class Start extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct($game,
            id: ST_START,
            type: StateType::GAME,
        );
    }

    function onEnteringState() {        
        if ($this->game->canPickMonster()) {
            return ST_PLAYER_PICK_MONSTER; 
        } else {
            return $this->game->redirectAfterPickMonster();
        }
    }
}