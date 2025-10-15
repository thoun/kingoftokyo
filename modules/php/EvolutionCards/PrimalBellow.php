<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;

class PrimalBellow extends EvolutionCard {
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
    }

    public function immediateEffect(Context $context) {
        $otherPlayersIds = $context->game->getOtherPlayersIds($context->currentPlayerId);
        foreach ($otherPlayersIds as $otherPlayerId) {
            $context->game->applyLosePoints($otherPlayerId, 2, $this);
        }
    }
}
