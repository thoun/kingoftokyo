<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class CornerStore extends PowerCard {
    public function immediateEffect(Context $context) {
        $context->game->applyGetPoints($context->currentPlayerId, 1, $this);
    }
}

?>
