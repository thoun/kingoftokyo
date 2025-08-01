<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\WickednessTiles;

use Bga\Games\KingOfTokyo\WickednessTiles\WickednessTile;
use Bga\Games\KingOfTokyo\Objects\Context;

class Skulking extends WickednessTile {
    public function resolvingDiceEffect(Context $context) {
        $playerId = $context->currentPlayerId;

        if ($context->dieSymbol == 1 && $context->dieCount >= 3) {
            $context->game->applyGetPoints($playerId, 1, $this);
        }
    }
}

?>