<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\WickednessTiles;

use Bga\Games\KingOfTokyo\WickednessTiles\WickednessTile;
use Bga\Games\KingOfTokyo\Objects\Context;

class Tireless extends WickednessTile {
    public function startTurnEffect(Context $context) {
        $playerId = $context->currentPlayerId;

        $context->game->applyGetEnergy($playerId, 1, 2000 + $this->type);
    }
}

?>