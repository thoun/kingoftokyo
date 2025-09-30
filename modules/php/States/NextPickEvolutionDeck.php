<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class NextPickEvolutionDeck extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct($game,
            id: ST_NEXT_PICK_EVOLUTION_DECK,
            type: StateType::GAME,
            updateGameProgression: true,
        );
    }

    function onEnteringState() {
        $turn = intval($this->game->getGameStateValue(MUTANT_EVOLUTION_TURN));
        if ($turn === 0) {
            // give 8 random evolutions to each players mutant deck
            $playersIds = $this->game->getPlayersIds();
            foreach($playersIds as $playerId) {
                $this->game->powerUpExpansion->evolutionCards->moveAllItemsInLocation('deck'.$playerId, 'mutantdeck');
            }
            $this->game->powerUpExpansion->evolutionCards->shuffle('mutantdeck');
            foreach($playersIds as $index => $playerId) {
                $this->game->powerUpExpansion->evolutionCards->pickItemsForLocation(8, 'mutantdeck', null, 'mutant'.$index);
            }
        }

        if ($turn >= 8) {
            $this->game->setOwnerIdForAllEvolutions();
            
            $this->game->goToState($this->game->redirectAfterPickEvolutionDeck());
        } else {
            $this->game->setGameStateValue(MUTANT_EVOLUTION_TURN, $turn + 1);

            $this->gamestate->setAllPlayersMultiactive();
            $this->game->goToState(ST_MULTIPLAYER_PICK_EVOLUTION_DECK);
        }
    }
}
