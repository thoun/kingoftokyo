<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

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

        return NextPlayer::class;
    }
}
