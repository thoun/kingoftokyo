<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class UnstoppableHydra extends EvolutionCard
{
    public function applyEffect(Context $context) {
        $context->game->applyGetHealth($context->currentPlayerId, 3, $this, $context->currentPlayerId);
        $context->game->incBaseDice($context->currentPlayerId, -1);
    }
}
