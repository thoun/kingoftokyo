<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class ToxicPetals extends PowerCard
{
    public function __construct() {
        $this->mindbugKeywords = [POISON];
    }

    public function applyEffect(Context $context) {
        $context->game->applyLosePoints($context->attackerPlayerId, $context->lostHearts, $this); 

        $context->game->removeCard($context->currentPlayerId, $this);
    }
}
