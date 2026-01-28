<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

use const Bga\Games\KingOfTokyo\PowerCards\FRENZY;

class EndTurn extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct($game,
            id: ST_END_TURN,
            type: StateType::GAME,
        );
    }

    function onEnteringState(int $activePlayerId) { 
        if ($this->game->powerUpExpansion->isActive()) {
            $EVOLUTION_TYPES_TO_REMOVE = [
                CAT_NIP_EVOLUTION, 
                MECHA_BLAST_EVOLUTION,
            ];
            foreach($EVOLUTION_TYPES_TO_REMOVE as $evolutionType) {
                $evolutions = $this->game->getEvolutionsOfType($activePlayerId, $evolutionType);
                if (count($evolutions) > 0) {
                    $this->game->removeEvolutions($activePlayerId, $evolutions);
                }
            }
        }

        // for Explosive Crystal : we cannot remove the card after use, because we must check onPlayerElimination
        // but if the player is not eliminated, we must remove the activated card
        $playerIds = $this->game->getPlayersIds();
        foreach ($playerIds as $playerId) {
            $cards = $this->game->powerCards->getPlayerVirtual($playerId);
            foreach ($cards as $card) {
                if ($card->activated && $card->activated->keyword !== FRENZY) {
                    $this->game->removeCard($playerId, $card);
                }
            }
        }

        return NextPlayer::class;
    }
}
