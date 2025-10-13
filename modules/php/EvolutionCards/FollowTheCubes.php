<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class FollowTheCubes extends EvolutionCard
{
    public function applyEffect(Context $context) {
        $targetPlayerEnergy = $context->game->getPlayerEnergy($context->targetPlayerId);
        if ($targetPlayerEnergy === $context->game->getMaxPlayerEnergy()) {
            // TODOMB add 2 [dieClaw]
        }
    }
}
