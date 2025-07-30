<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\WickednessTiles;

use Bga\Games\KingOfTokyo\WickednessTiles\WickednessTile;
use Bga\Games\KingOfTokyo\Game;
use Bga\Games\KingOfTokyo\Objects\Context;

class FinalPush extends WickednessTile {
    public function immediateEffect(Game $game, Context $context) {
        $playerId = $context->currentPlayerId;
        $logTileType = 2000 + $this->type;
        
        $game->applyGetHealth($playerId, 2, $logTileType, $playerId);
        $game->applyGetEnergy($playerId, 2, $logTileType);
        $game->setGameStateValue(FINAL_PUSH_EXTRA_TURN, 1);
    }
}

?>