<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\WickednessTiles;

use Bga\Games\KingOfTokyo\WickednessTiles\WickednessTile;
use Bga\Games\KingOfTokyo\Objects\Context;

class Underdog extends WickednessTile {
    public function onApplyDamageEffect(Context $context) {
        if ($context->smasherPoints === null) {
            return; // only apply on smashes
        }

        if ($context->smasherPoints < $context->game->getPlayerScore($context->targetPlayerId)) {
            $context->game->applyLosePoints($context->targetPlayerId, 1, $this);
            $context->game->applyGetPoints($context->attackerPlayerId, 1, $this);
        }
    }
}

?>