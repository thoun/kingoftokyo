<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class StartGame extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct($game,
            id: ST_START_GAME,
            type: StateType::GAME,
        );
    }

    function onEnteringState() { 
        if ($this->game->isHalloweenExpansion()) {
            $this->game->powerCards->moveAllItemsInLocation('costumedeck', 'deck');
            $this->game->powerCards->moveAllItemsInLocation('costumediscard', 'deck');
        }
        $this->game->powerCards->shuffle('deck'); 

        // TODO $this->game->debugSetupBeforePlaceCard();
        $cards = $this->game->placeNewCardsOnTable();
        // TODO $this->game->debugSetupAfterPlaceCard();

        $this->notify->all("setInitialCards", '', [
            'cards' => $cards,
            'deckCardsCount' => $this->game->powerCards->getDeckCount(),
            'topDeckCard' => $this->game->powerCards->getTopDeckCard(),
        ]);

        return ST_PLAYER_BEFORE_START_TURN;
    }
}
