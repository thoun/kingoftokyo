<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class MaximumEffort extends PowerCard
{
    public function __construct() {
        $this->mindbugKeywords = [FRENZY];
    }

    public function applyEffect(Context $context) {
        $context->game->incBaseDice($context->currentPlayerId, -1);

        $context->game->removeCard($context->currentPlayerId, $this);
    }
}
