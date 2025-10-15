<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;

class AdaptingTechnology extends EvolutionCard {
    public function __construct()
    {
        $this->evolutionType = PERMANENT;
    }

    public function immediateEffect(Context $context) {
        $context->game->setEvolutionTokens($context->currentPlayerId, $this, $context->game->getTokensByEvolutionType(ADAPTING_TECHNOLOGY_EVOLUTION));
    }
}
