<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

class StrangeDesign extends PowerCard
{
    public function __construct() {
        $this->mindbugKeywords = [TOUGH];
    }

    public function applyEffect(Context $context) {
        $damage = new Damage($context->currentPlayerId, 2, $context->currentPlayerId, $this);

        $context->game->removeCard($context->currentPlayerId, $this);

        return [$damage];
    }
}
