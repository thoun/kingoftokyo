<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\Objects\Context;

use const Bga\Games\KingOfTokyo\PowerCards\HUNTER;

class FollowTheCubes extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
        $this->mindbugKeywords = [HUNTER];
    }

    public function applyEffect(Context $context) {
        $targetPlayerEnergy = $context->game->getPlayerEnergy($context->targetPlayerId);
        if ($targetPlayerEnergy === $context->game->getMaxPlayerEnergy()) {
            // TODOMB add 2 [dieClaw]
        }
        $context->game->removeEvolution($context->currentPlayerId, $this);
    }
}
