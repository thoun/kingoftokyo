<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class NaturalSelection extends PowerCard {
    public function immediateEffect(Context $context) {
        $context->game->applyGetEnergy($context->currentPlayerId, 4, $this);
        $context->game->applyGetHealth($context->currentPlayerId, 4, $this, $context->currentPlayerId);

        // TODO endTurn effect : move here, but also make it dodgeable with Wings? Confirm with publisher?
    }
}
