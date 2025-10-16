<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class ScaryFace extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = PERMANENT;
    }

    public function applyEffect(Context $context) {
        /*$context->game->applyGetHealth($context->currentPlayerId, 3, $this, $context->currentPlayerId);


        if ($context->smasherPoints < $context->game->getPlayerScore($context->targetPlayerId)) {
            $context->game->applyLosePoints($context->targetPlayerId, 1, $this);
            $context->game->applyGetPoints($context->attackerPlayerId, 1, $this);
        } TODOMB TOCHECK what if multiple players ? */
    }
}
