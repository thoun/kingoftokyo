<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class EndMindbug extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct($game,
            id: ST_END_MINDBUG,
            type: StateType::GAME,
        );
    }

    function onEnteringState(int $activePlayerId) { 
        $mindbuggedPlayerId = $this->game->mindbugExpansion->getMindbuggedPlayer();
        $this->game->gamestate->changeActivePlayer($mindbuggedPlayerId);
        
        $this->game->globals->set(MINDBUGGED_PLAYER, null);

        $this->game->notify->all("mindbugPlayer", /*TODOMB clienttranslate*/('${player_name} finishes his mindbugged turn, ${player_name2} can start again his turn at the Roll dice phase'), [
            'activePlayerId' => $activePlayerId,
            'mindbuggedPlayerId' => null,
            'player_name' => $this->game->getPlayerNameById($activePlayerId),
            'player_name2' => $this->game->getPlayerNameById($mindbuggedPlayerId),
        ]);

        // reinit some stuff for the player 
        $this->game->startTurnInitDice();

        return ST_INITIAL_DICE_ROLL;
    }
}
