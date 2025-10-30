<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\Objects\Context;

use const Bga\Games\KingOfTokyo\PowerCards\TOUGH;

class UndeadMummy extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
        $this->mindbugKeywords = [TOUGH];
    }

    public function applyEffect(Context $context) {
        $context->game->removeEvolution($context->currentPlayerId, $this, false, 5000);
    }
}
