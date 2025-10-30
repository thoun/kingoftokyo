<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

use const Bga\Games\KingOfTokyo\PowerCards\FRENZY;

class ChewPinchCatchAndSmack extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
        $this->mindbugKeywords = [FRENZY];
    }

    public function applyEffect(Context $context) {
        $context->game->applyLosePoints($context->currentPlayerId, 2, $this);
        $damage = new Damage($context->currentPlayerId, 2, $context->currentPlayerId, $this);
        $context->game->applyLoseEnergy($context->currentPlayerId, 2, $this);
        return [$damage];
    }
}
