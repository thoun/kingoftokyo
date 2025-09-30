<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class PickMonsterNextPlayer extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct($game,
            id: ST_PICK_MONSTER_NEXT_PLAYER,
            type: StateType::GAME,
        );
    }

    function onEnteringState() {        
        $playerId = $this->game->activeNextPlayer();
        $this->game->giveExtraTime($playerId);

        if (intval($this->game->getUniqueValueFromDB( "SELECT count(*) FROM player WHERE player_monster = 0")) == 0) {

            if ($this->game->powerUpExpansion->isActive() && !$this->game->powerUpExpansion->isPowerUpMutantEvolution()) {
                $this->game->setOwnerIdForAllEvolutions();
            }

            $this->game->goToState($this->game->redirectAfterPickMonster());
        } else {
            return ST_PLAYER_PICK_MONSTER;
        }
    }
}