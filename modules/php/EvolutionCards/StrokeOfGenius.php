<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;

class StrokeOfGenius extends EvolutionCard {
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
    }

    public function immediateEffect(Context $context) {
        $context->game->applyGetEnergy($context->currentPlayerId, $context->game->getPlayer($context->currentPlayerId)->turnEnergy, $this);
    }
}
