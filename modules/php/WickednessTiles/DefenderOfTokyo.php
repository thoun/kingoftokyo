<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\WickednessTiles;

use Bga\Games\KingOfTokyo\WickednessTiles\WickednessTile;
use Bga\Games\KingOfTokyo\Objects\Context;

class DefenderOfTokyo extends WickednessTile {
    public function startTurnEffect(Context $context) {
        if ($context->currentPlayerInTokyo) {
            $this->applyDefenderOfTokyo($context);
        }
    }
    
    public function enteringTokyoEffect(Context $context) {
        $this->applyDefenderOfTokyo($context);
    }

    private function applyDefenderOfTokyo(Context $context) {
        $otherPlayersIds = $context->game->getOtherPlayersIds($context->currentPlayerId);
        foreach ($otherPlayersIds as $otherPlayerId) {
            $context->game->applyLosePoints($otherPlayerId, 1, $this);
        }
    }
}

?>