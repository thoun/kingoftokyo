<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;

class EmergencyBattery extends EvolutionCard {
    public function immediateEffect(Context $context) {
        $context->game->applyGetEnergy($context->currentPlayerId, 3, $this);
    }
}
