<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\Objects\Context;

use const Bga\Games\KingOfTokyo\PowerCards\TOUGH;

class ThreeTimesAsSturdy extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
        $this->mindbugKeywords = [TOUGH];
    }

    public function applyEffect(Context $context) {
        if ($context->lostHearts === 3) {
            $context->game->applyGetHealth($context->currentPlayerId, 3, $this, $context->currentPlayerId);
        }
        $context->game->removeEvolution($context->currentPlayerId, $this);
    }
}
