<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;

class TuneUp extends EvolutionCard {
    public function applyEffect(Context $context) {
        $context->game->applyGetHealth($context->currentPlayerId, 4, $this, $context->currentPlayerId);
        $context->game->applyGetEnergy($context->currentPlayerId, 2, $this);
        $context->game->removeCard($context->currentPlayerId, $this, false/*, 5000*/);
        $context->game->goToState(ST_NEXT_PLAYER);
    }
}