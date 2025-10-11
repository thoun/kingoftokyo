<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class DeadlyShell extends PowerCard
{
    public function __construct() {
        $this->mindbugKeywords = [POISON, TOUGH];
    }

    public function applyEffect(Context $context) {
        $context->game->removeCard($context->currentPlayerId, $this);
    }
}
