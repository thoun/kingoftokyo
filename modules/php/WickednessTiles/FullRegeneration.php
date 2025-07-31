<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\WickednessTiles;

use Bga\Games\KingOfTokyo\WickednessTiles\WickednessTile;
use Bga\Games\KingOfTokyo\Objects\Context;

class FullRegeneration extends WickednessTile {
    public function immediateEffect(Context $context) {
        $playerId = $context->currentPlayerId;
        $logTileType = 2000 + $this->type;

        if ($context->game->canGainHealth($playerId)) {
            $context->game->applyGetHealthIgnoreCards($playerId, $context->game->getPlayerMaxHealth($playerId), $logTileType, $playerId);
        }
    }

    public function incMaxHealth(Context $context) {
        return 2;
    }
}

?>