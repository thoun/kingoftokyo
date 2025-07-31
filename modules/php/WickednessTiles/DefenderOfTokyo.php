<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\WickednessTiles;

use Bga\Games\KingOfTokyo\WickednessTiles\WickednessTile;
use Bga\Games\KingOfTokyo\Objects\Context;

class DefenderOfTokyo extends WickednessTile {
    public function startTurnEffect(Context $context) {
        if ($context->currentPlayerInTokyo) {
            $context->game->applyDefenderOfTokyo($context->currentPlayerId, 2000 + $this->type, 1);
        }
    }
    
    public function enteringTokyoEffect(Context $context) {
        $context->game->applyDefenderOfTokyo($context->currentPlayerId, 2000 + $this->type, 1);
    }
}

?>