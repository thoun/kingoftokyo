<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class EnergyArmor extends PowerCard
{
    public function __construct() {
        $this->mindbugKeywords = [TOUGH];
    }

    public function applyEffect(Context $context) {
        $context->game->applyGetEnergy($context->currentPlayerId, $context->lostHearts, $this); // TODOMB test

        $context->game->removeCard($context->currentPlayerId, $this);
    }
}
