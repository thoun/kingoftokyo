<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;
use KOT\Objects\Damage;

class EnterTokyoApplyBurrowing extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_ENTER_TOKYO_APPLY_BURROWING,
            type: StateType::GAME,
        );
    }

    public function onEnteringState(int $activePlayerId) {
        $leaversWithUnstableDNA = $this->game->getLeaversWithUnstableDNA();
        $nextState = count($leaversWithUnstableDNA) >= 1 && $leaversWithUnstableDNA[0] !== $activePlayerId
            ? \ST_MULTIPLAYER_LEAVE_TOKYO_EXCHANGE_CARD
            : \ST_MULTIPLAYER_BEFORE_ENTERING_TOKYO;

        $damages = [];

        $leaversWithBurrowing = $this->game->getLeaversWithBurrowing();
        foreach ($leaversWithBurrowing as $leaverWithBurrowingId) {
            $countBurrowing = $this->game->countCardOfType($leaverWithBurrowingId, \BURROWING_CARD);
            if ($countBurrowing > 0) {
                $damages[] = new Damage($activePlayerId, $countBurrowing, $leaverWithBurrowingId, \BURROWING_CARD);
            }
        }

        $leaversWithJaggedTactician = $this->game->getLeaversWithJaggedTactician();
        foreach ($leaversWithJaggedTactician as $leaverWithJaggedTacticianId) {
            $countJaggedTactician = $this->game->countCardOfType($leaverWithJaggedTacticianId, \JAGGED_TACTICIAN_CARD);
            if ($countJaggedTactician > 0) {
                $damages[] = new Damage($activePlayerId, $countJaggedTactician, $leaverWithJaggedTacticianId, \JAGGED_TACTICIAN_CARD);
                $this->game->applyGetEnergy($leaverWithJaggedTacticianId, $countJaggedTactician, \JAGGED_TACTICIAN_CARD);
            }
        }

        $this->game->setGlobalVariable(\BURROWING_PLAYERS, []);
        $this->game->setGlobalVariable(\JAGGED_TACTICIAN_PLAYERS, []);

        $this->game->goToState($nextState, $damages);
    }
}

