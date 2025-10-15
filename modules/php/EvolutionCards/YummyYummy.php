<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;

class YummyYummy extends EvolutionCard {
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
    }

    public function immediateEffect(Context $context) {
        $context->game->applyGetHealth($context->currentPlayerId, 2, $this, $context->currentPlayerId);
        $context->game->applyGetEnergy($context->currentPlayerId, 1, $this);
    }
}
