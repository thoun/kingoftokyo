<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\Objects\Context;

use const Bga\Games\KingOfTokyo\PowerCards\SNEAKY;

class SharkAttack extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
        $this->mindbugKeywords = [SNEAKY];
    }

    public function applyEffect(Context $context) {
        $playersInTokyo = $context->game->getPlayersIdsInTokyo();
        foreach ($playersInTokyo as $playerInTokyo) {
            $context->game->applyLosePoints($playerInTokyo, 1, $this);
        }
        $context->game->removeEvolution($context->currentPlayerId, $this);
    }
}
