<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;

class AngerBatteries extends EvolutionCard {
    public function immediateEffect(Context $context) {
        $damageCount = $context->game->getDamageTakenThisTurn($context->currentPlayerId);
        $context->game->applyGetEnergy($context->currentPlayerId, $damageCount, $this);
    }
}
