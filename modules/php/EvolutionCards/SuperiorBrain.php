<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class SuperiorBrain extends EvolutionCard
{
    public function applyEffect(Context $context) {
        $context->game->applyGetPoints($context->currentPlayerId, 1, $this);
    }
}
