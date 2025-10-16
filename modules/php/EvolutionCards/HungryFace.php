<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class HungryFace extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
    }

    public function applyEffect(Context $context) {
        $dice = $context->game->getPlayerRolledDice($context->currentPlayerId, false, false, false);
        $diceCounts = $context->game->getRolledDiceCounts($context->currentPlayerId, $dice, false);

        if ($diceCounts[5] === 3) {
            $context->game->globals->set(\NEXT_POWER_CARD_COST_REDUCTION, 3);
        }
    }
}
