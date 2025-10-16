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
        $this->evolutionType = PERMANENT;
        $this->mindbugKeywords = [FRENZY];
    }

    public function applyEffect(Context $context) {
        $context->game->applyLosePoints($context->currentPlayerId, 2, $this);
        $damages = new Damage($context->currentPlayerId, 2, $context->currentPlayerId, $this);
        $context->game->applyLoseEnergy($context->currentPlayerId, 2, $this);
        
        
        // TODOMB? $context->game->removeEvolution($context->currentPlayerId, $this);


        return $damages; // TODOMB test
    }
}
