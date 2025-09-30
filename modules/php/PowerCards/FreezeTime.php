<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class FreezeTime extends PowerCard {
    public function immediateEffect(Context $context) {
        if ($context->currentPlayerId == intval($context->game->getActivePlayerId())) {
            $diceCounts = $context->game->getGlobalVariable(DICE_COUNTS, true);
            if ($diceCounts[1] >= 3 && $context->game->mindbugExpansion->canGetExtraTurn()) {
                $context->game->incGameStateValue(FREEZE_TIME_MAX_TURNS, 1);
            }
        }
    }
}
