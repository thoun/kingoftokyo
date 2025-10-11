<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class ElectroWhip extends PowerCard
{
    public function __construct() {
        $this->mindbugKeywords = [POISON];
    }

    public function applyEffect(Context $context) {
        $context->game->applyLoseEnergy($context->attackerPlayerId, $context->lostHearts, $this); // TODOMB test

        $context->game->removeCard($context->currentPlayerId, $this);
    }
}
