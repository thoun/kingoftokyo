<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;

class SuperiorAlienTechnology extends EvolutionCard {
    public function immediateEffect(Context $context) {
        $context->game->applyEvolutionEffectsRefreshBuyCardArgsIfNeeded($context->currentPlayerId);
    }
}
