<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;
use KOT\Objects\Damage;

class EnterTokyo extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_ENTER_TOKYO,
            type: StateType::GAME,
        );
    }

    public function onEnteringState(int $activePlayerId) {
        $this->game->setGlobalVariable(\SMASHED_PLAYERS_IN_TOKYO, []);

        $damages = [];

        $preventEnterTokyo = (bool)$this->game->getGameStateValue(\PREVENT_ENTER_TOKYO);
        if (
            !$this->game->getPlayer($activePlayerId)->eliminated
            && !$this->game->inTokyo($activePlayerId)
            && !$preventEnterTokyo
        ) {
            $this->game->moveToTokyoFreeSpot($activePlayerId);

            if ($this->game->getPlayer($activePlayerId)->turnEnteredTokyo) {
                $countGammaBlast = $this->game->countCardOfType($activePlayerId, \GAMMA_BLAST_CARD);
                if ($countGammaBlast > 0) {
                    $otherPlayersIds = $this->game->getOtherPlayersIds($activePlayerId);
                    foreach ($otherPlayersIds as $playerId) {
                        $damages[] = new Damage($playerId, $countGammaBlast, $activePlayerId, \GAMMA_BLAST_CARD);
                    }
                }
            }
        }

        if ($preventEnterTokyo) {
            $this->game->setGameStateValue(\PREVENT_ENTER_TOKYO, 0);
        }

        $nextState = $this->game->powerUpExpansion->isActive()
            ? \ST_PLAYER_AFTER_ENTERING_TOKYO
            : $this->game->redirectAfterEnterTokyo($activePlayerId);

        $this->game->goToState($nextState, $damages);
    }
}

