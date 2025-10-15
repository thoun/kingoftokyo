<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class ThreeTimesAsSturdy extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = PERMANENT;
    }

    public function applyEffect(Context $context) {
        $context->game->applyGetHealth($context->currentPlayerId, 3, $this, $context->currentPlayerId);
    }
}
