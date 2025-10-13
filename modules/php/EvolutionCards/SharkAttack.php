<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class SharkAttack extends EvolutionCard
{
    public function applyEffect(Context $context) {
        $playersInTokyo = $context->game->getPlayersIdsInTokyo();
        foreach ($playersInTokyo as $playerInTokyo) {
            $context->game->applyLosePoints($playerInTokyo, 1, $this);
        }
    }
}
