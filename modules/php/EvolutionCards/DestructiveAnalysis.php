<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;

class DestructiveAnalysis extends EvolutionCard {
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
    }

    public function immediateEffect(Context $context) {
        $dice = $context->game->getPlayerRolledDice($context->currentPlayerId, true, false, false);
        $diceCounts = $context->game->getRolledDiceCounts($context->currentPlayerId, $dice, false, false);
        $rolledSmashes = $diceCounts[6];
        if ($rolledSmashes > 0) {
            $context->game->applyGetEnergy($context->currentPlayerId, $rolledSmashes, $this);
        }
    }
}
