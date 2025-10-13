<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

class ChewPinchCatchAndSmack extends EvolutionCard
{
    public function applyEffect(Context $context) {
        $context->game->applyLosePoints($context->currentPlayerId, 2, $this);
        $damages = new Damage($context->currentPlayerId, 2, $context->currentPlayerId, $this);
        $context->game->applyLoseEnergy($context->currentPlayerId, 2, $this);

        return $damages; // TODOMB test
    }
}
