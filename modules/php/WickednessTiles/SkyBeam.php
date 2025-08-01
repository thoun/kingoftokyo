<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\WickednessTiles;

use Bga\Games\KingOfTokyo\WickednessTiles\WickednessTile;
use Bga\Games\KingOfTokyo\Objects\Context;

class SkyBeam extends WickednessTile {
    public function resolvingDiceEffect(Context $context) {
        $playerId = $context->currentPlayerId;

        if ($context->dieSymbol == 4 && $context->dieCount > 0) {
            $context->game->applyGetHealth($playerId, $context->dieCount, $this, $playerId);
        }
        if ($context->dieSymbol == 5 && $context->dieCount > 0) {
            $context->game->applyGetEnergy($playerId, $context->dieCount, $this, $playerId);
        }
    }
}

?>