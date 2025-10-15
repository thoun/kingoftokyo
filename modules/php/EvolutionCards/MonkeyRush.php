<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;

class MonkeyRush extends EvolutionCard {
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
    }

    public function immediateEffect(Context $context) {
        $context->game->moveToTokyoFreeSpot($context->currentPlayerId);
        if (!$context->game->tokyoHasFreeSpot()) {
            $context->game->goToState($context->game->redirectAfterHalfMovePhase());
        }
    }
}
