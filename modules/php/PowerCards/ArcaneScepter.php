<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class ArcaneScepter extends PowerCard
{
    public function __construct() {
        $this->mindbugKeywords = [SNEAKY];
    }

    public function applyEffect(Context $context) {
        // TODOMB TODOCHECK do we take the card right now or when resolving SNEAKY

        // TODOMB

        $context->game->removeCard($context->currentPlayerId, $this);
    }
}
