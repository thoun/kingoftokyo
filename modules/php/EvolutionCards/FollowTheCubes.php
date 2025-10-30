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

    public function addSmashesOrder(): int {
        return 1;
    }

    public function addSmashes(Context $context): int {
        if (!$this->activated) {
            return 0;
        }
        
        $targetPlayerEnergy = $context->game->getPlayerEnergy($this->activated->targetPlayerId);
        if ($targetPlayerEnergy === $context->game->getMaxPlayerEnergy()) {
            return 2;
        }
        return 0;
    }

    public function applyEffect(Context $context) {
        $context->game->removeEvolution($context->currentPlayerId, $this);
    }
}
