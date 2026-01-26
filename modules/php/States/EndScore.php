<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

const ST_END_GAME = 99;

class EndScore extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct($game,
            id: ST_END_SCORE,
            type: StateType::GAME,
        );
    }

    function onEnteringState() {
        $players = $this->game->getPlayers(true);
        $playerCount = count($players);
        $remainingPlayers = $this->game->getRemainingPlayers();
        $pointsWin = false;
        foreach($players as &$player) {
            if ($player->score >= MAX_POINT) {
                if ($player->score > MAX_POINT) {
                    $player->score = MAX_POINT;
                    $this->bga->playerScore->set($player->id, MAX_POINT, null);
                }
                $pointsWin = true;
            } 
        }

        // in case everyone is dead, no ranking
        if ($remainingPlayers == 0) {
            $this->bga->playerScore->setAll(0, null);
            $this->bga->playerScoreAux->setAll(0, null);
        }

        $eliminationWin = $remainingPlayers == 1;

        $this->game->setStat($pointsWin ? 1 : 0, 'pointsWin');
        $this->game->setStat($eliminationWin ? 1 : 0, 'eliminationWin');
        $this->game->setStat($remainingPlayers / (float) $playerCount, 'survivorRatio');

        foreach($players as $player) {            
            $this->game->setStat($player->eliminated ? 0 : 1, 'survived', $player->id);

            if (!$player->eliminated) {
                if ($player->score >= MAX_POINT) {
                    $this->game->setStat(1, 'pointsWin', $player->id);
                }
                if ($eliminationWin) {
                    $this->game->setStat(1, 'eliminationWin', $player->id);
                }

                if ($pointsWin) {
                    $this->game->setStat($player->score, 'endScore', $player->id);
                }
                $this->game->setStat($player->health, 'endHealth', $player->id);
            }            
        }

        return ST_END_GAME;
    }
}