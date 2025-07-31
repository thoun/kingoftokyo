<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\WickednessTiles;

use Bga\Games\KingOfTokyo\WickednessTiles\WickednessTile;
use Bga\Games\KingOfTokyo\Objects\Context;

class WidespreadPanic extends WickednessTile {
    public function immediateEffect(Context $context) {
        $playerId = $context->currentPlayerId;
        $logTileType = 2000 + $this->type;

        $otherPlayersIds = $context->game->getOtherPlayersIds($playerId);
        foreach ($otherPlayersIds as $otherPlayerId) {
            $context->game->applyLosePoints($otherPlayerId, 4, $logTileType);
        }
        $context->game->removeWickednessTiles($playerId, [$this]);
    }
}

?>