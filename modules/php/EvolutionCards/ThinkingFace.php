<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class ThinkingFace extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
    }

    public function applyEffect(Context $context) {
        if (true) { // TODOMB
            $context->game->applyGetHealth($context->currentPlayerId, 3, $this, $context->currentPlayerId);
            //$lowest = $context->game->getMinPlayerHealth() TODOMB TOCHECK what in cas of tie?
        }
    }
}
