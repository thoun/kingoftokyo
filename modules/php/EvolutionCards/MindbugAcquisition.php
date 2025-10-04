<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;

class MindbugAcquisition extends EvolutionCard {    
    public function immediateEffect(Context $context) {
        $context->game->applyLoseEnergy($context->currentPlayerId, 6, $this); // TOCHECK can we use it if less than 6 energy?
        $context->game->mindbugExpansion->applyGetMindbugTokens($context->currentPlayerId, 1, $this);
    }
}
