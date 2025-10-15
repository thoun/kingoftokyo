<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;

class PandaMonium extends EvolutionCard {
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
    }

    public function immediateEffect(Context $context) {
        $context->game->applyGetEnergy($context->currentPlayerId, 6, $this);
        $otherPlayersIds = $context->game->getOtherPlayersIds($context->currentPlayerId);
        foreach ($otherPlayersIds as $otherPlayerId) {
            $context->game->applyGetEnergy($otherPlayerId, 3, $this);
        }
        $context->game->applyEvolutionEffectsRefreshBuyCardArgsIfNeeded($context->currentPlayerId, true);
    }
}
