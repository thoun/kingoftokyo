<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class AfterCardIsBought extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_AFTER_WHEN_CARD_IS_BOUGHT,
            type: StateType::GAME,
        );
    }

    public function onEnteringState(int $activePlayerId) {
        $cardBeingBought = $this->game->getGlobalVariable(\CARD_BEING_BOUGHT);

        if ($cardBeingBought->allowed) {
            $this->game->applyBuyCard(
                $cardBeingBought->playerId,
                $cardBeingBought->cardId,
                $cardBeingBought->cost,
                $cardBeingBought->useSuperiorAlienTechnology,
                $cardBeingBought->useBobbingForApples
            );
        } else {
            $this->game->goToState(\ST_PLAYER_BUY_CARD);
        }
    }
}

