<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\WickednessTiles;

use Bga\Games\KingOfTokyo\WickednessTiles\WickednessTile;
use Bga\Games\KingOfTokyo\Objects\Context;

class SonicBoomer extends WickednessTile {
    public function startTurnEffect(Context $context) {
        $playerId = $context->currentPlayerId;

        $context->game->applyGetPoints($playerId, 1, $this);
    }
}

?>