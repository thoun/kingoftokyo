<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;

class AlienScourge extends EvolutionCard {
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
    }

    public function immediateEffect(Context $context) {
        $context->game->applyGetPoints($context->currentPlayerId, 2, $this);
    }
}
