<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

class DuskRitual extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
    }

    // TODOPUHA to code case 78: return /*_TODOPUHA*/("Play this card right after you buy a [keep] card. Gain 2[Heart] and 2[Energy].");
}
