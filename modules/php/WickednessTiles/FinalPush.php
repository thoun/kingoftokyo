<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\WickednessTiles;

use Bga\Games\KingOfTokyo\WickednessTiles\WickednessTile;
use Bga\Games\KingOfTokyo\Objects\Context;

class FinalPush extends WickednessTile {
    public function immediateEffect(Context $context) {
        $playerId = $context->currentPlayerId;
        $logTileType = 2000 + $this->type;
        
        $context->game->applyGetHealth($playerId, 2, $logTileType, $playerId);
        $context->game->applyGetEnergy($playerId, 2, $logTileType);
        $context->game->setGameStateValue(FINAL_PUSH_EXTRA_TURN, 1);
    }
}

?>