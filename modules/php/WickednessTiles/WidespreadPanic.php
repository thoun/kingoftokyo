<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\WickednessTiles;

use Bga\Games\KingOfTokyo\WickednessTiles\WickednessTile;
use Bga\Games\KingOfTokyo\Objects\Context;

class WidespreadPanic extends WickednessTile {
    public function immediateEffect(Context $context) {
        $playerId = $context->currentPlayerId;

        $otherPlayersIds = $context->game->getOtherPlayersIds($playerId);
        foreach ($otherPlayersIds as $otherPlayerId) {
            $context->game->applyLosePoints($otherPlayerId, 4, $this);
        }
        $context->game->wickednessExpansion->removeWickednessTiles($playerId, [$this]);
    }
}

?>