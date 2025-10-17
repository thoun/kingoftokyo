<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class SneakyAlloy extends PowerCard
{
    public function __construct() {
        $this->mindbugKeywords = [SNEAKY];
    }

    public function applyEffect(Context $context) { // TODOMB
        $context->game->removeCard($context->currentPlayerId, $this);
    }
}
