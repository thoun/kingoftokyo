<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;

class GammaBreath extends EvolutionCard {
    public function __construct()
    {
        $this->evolutionType = PERMANENT;
    }

}
