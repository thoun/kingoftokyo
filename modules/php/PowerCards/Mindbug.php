<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class Mindbug extends PowerCard
{
    public function immediateEffect(Context $context) {
        $context->game->mindbugExpansion->applyGetMindbugTokens($context->currentPlayerId, 1, $this);
    }
}
