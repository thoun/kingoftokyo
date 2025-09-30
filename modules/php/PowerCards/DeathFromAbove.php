<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class DeathFromAbove extends PowerCard {
    public function immediateEffect(Context $context) {
        $context->game->applyGetPoints($context->currentPlayerId, 2, $this);
        $context->game->replacePlayersInTokyo($context->currentPlayerId);
    }
}

?>
