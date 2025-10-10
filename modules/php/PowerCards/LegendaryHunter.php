<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class LegendaryHunter extends PowerCard
{
    public function __construct() {
        $this->mindbugKeywords = [HUNTER];
    }

    public function applyEffect(Context $context) {
        if ($context->game->getPlayer($context->targetPlayerId)->eliminated) {
            $context->game->applyGetPoints($context->currentPlayerId, 4, $this);
        }

        $context->game->removeCard($context->currentPlayerId, $this);
    }
}
