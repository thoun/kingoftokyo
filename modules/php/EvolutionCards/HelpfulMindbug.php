<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class HelpfulMindbug extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
    }
    
    public function immediateEffect(Context $context) {
        $diceCounts = $context->game->getGlobalVariable(\DICE_COUNTS, true);
        $context->game->applyGetHealth($context->currentPlayerId, $diceCounts[4], $this, $context->currentPlayerId);
        $context->game->applyGetEnergy($context->currentPlayerId, $diceCounts[5], $this);
    }
}
