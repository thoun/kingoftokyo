<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class AncestralDefense extends PowerCard
{
    public function __construct() {
        $this->mindbugKeywords = [TOUGH];
    }

    public function applyEffect(Context $context) {
        // TODOMB

        $context->game->removeCard($context->currentPlayerId, $this);

        // TODOMB test
    }
}
