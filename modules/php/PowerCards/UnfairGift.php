<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class UnfairGift extends PowerCard
{
    public function __construct() {
        $this->mindbugKeywords = [FRENZY];
    }

    public function applyEffect(Context $context) {
        $context->game->applyLosePoints($context->currentPlayerId, 5, $this);

        $context->game->removeCard($context->currentPlayerId, $this);
    }
}
