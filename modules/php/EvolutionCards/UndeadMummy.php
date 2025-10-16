<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use const Bga\Games\KingOfTokyo\PowerCards\TOUGH;

class UndeadMummy extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
        $this->mindbugKeywords = [TOUGH];
    }

    // TODOMB
     //   $context->game->removeEvolution($context->currentPlayerId, $this);
}
