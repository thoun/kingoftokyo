<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

class EatsShootsAndLeaves extends EvolutionCard {
    public function immediateEffect(Context $context) {
        $outsideTokyoPlayersIds = $context->game->getPlayersIdsOutsideTokyo();
        $damages = [];
        foreach ($outsideTokyoPlayersIds as $outsideTokyoPlayerId) {
            $damages[] = new Damage($outsideTokyoPlayerId, 2, $context->currentPlayerId, $this);
        }

        $context->game->applyGetEnergy($context->currentPlayerId, 1, $this);
        $context->game->leaveTokyo($context->currentPlayerId);
        return $damages;
    }
}
