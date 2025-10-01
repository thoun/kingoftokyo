<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class Heal extends PowerCard {
    public function immediateEffect(Context $context) {
        $context->game->applyGetHealth($context->currentPlayerId, 2, $this, $context->currentPlayerId);
    }
}

?>
