<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class Energize extends PowerCard {
    public function immediateEffect(Context $context) {
        $context->game->applyGetEnergy($context->currentPlayerId, 9, $this);
    }
}

?>
