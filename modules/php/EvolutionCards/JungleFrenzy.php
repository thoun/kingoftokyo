<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;

class JungleFrenzy extends EvolutionCard {
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
    }

    public function immediateEffect(Context $context) {
        if ($context->game->mindbugExpansion->canGetExtraTurn()) {
// TODOMB add a warning when playing the card
            $context->game->setGameStateValue(JUNGLE_FRENZY_EXTRA_TURN, 1);
        }
    }
}

?>
