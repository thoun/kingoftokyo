<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class LeaveTokyoApplyJets extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_LEAVE_TOKYO_APPLY_JETS,
            type: StateType::GAME,
            transitions: [
                'next' => \ST_ENTER_TOKYO_APPLY_BURROWING,
            ],
        );
    }

    public function onEnteringState(int $activePlayerId) {
        $jetsDamages = $this->game->getGlobalVariable(\JETS_DAMAGES);

        $this->game->goToState(\ST_ENTER_TOKYO_APPLY_BURROWING, $jetsDamages);
    }
}

