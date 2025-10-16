<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class UnstoppableHydra extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
    }

    public function applyEffect(Context $context) {
        $context->game->playEvolutionToTable($context->currentPlayerId, $this, '');
        $context->game->removeEvolution($context->currentPlayerId, $this, false, 5000);

        $context->game->applyGetHealth($context->currentPlayerId, 3, $this, $context->currentPlayerId);
        $context->game->incBaseDice($context->currentPlayerId, -1);
    }
}
