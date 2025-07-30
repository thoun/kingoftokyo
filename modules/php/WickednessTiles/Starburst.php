<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\WickednessTiles;

use Bga\Games\KingOfTokyo\WickednessTiles\WickednessTile;
use Bga\Games\KingOfTokyo\Game;
use Bga\Games\KingOfTokyo\Objects\Context;

class Starburst extends WickednessTile {
    public function immediateEffect(Game $game, Context $context) {
        $playerId = $context->currentPlayerId;
        $logTileType = 2000 + $this->type;

        $game->applyGetEnergy($playerId, 12, $logTileType);
        $game->removeWickednessTiles($playerId, [$this]);
    }
}

?>