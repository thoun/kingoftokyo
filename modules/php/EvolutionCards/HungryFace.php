<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class HungryFace extends EvolutionCard
{
    public function applyEffect(Context $context) {
        if (true) { // TODOMB
            $context->game->globals->set(\NEXT_POWER_CARD_COST_REDUCTION, 3);
        }
    }
    // TODOMB
}
