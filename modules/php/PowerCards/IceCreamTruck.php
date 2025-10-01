<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class IceCreamTruck extends PowerCard {
    public function immediateEffect(Context $context) {
        $context->game->applyGetPoints($context->currentPlayerId, 1, $this);
        $context->game->applyGetHealth($context->currentPlayerId, 2, $this, $context->currentPlayerId);
    }
}

?>
