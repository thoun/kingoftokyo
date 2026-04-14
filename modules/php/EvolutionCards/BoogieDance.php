<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

class BoogieDance extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = PERMANENT;
    }

    // TODOPUHA to code case 72: return /*_TODOPUHA*/("At the beginning of your turn, give 1[Energy] to the <i>Owner</i> of this card or lose 1[Heart]."); // TODOPUHA TOCHECK what if owner dies?
}
