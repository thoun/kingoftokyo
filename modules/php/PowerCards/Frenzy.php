<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class Frenzy extends PowerCard {
    public function immediateEffect(Context $context) {
        $activePlayerId = intval($context->game->getActivePlayerId());
        if ($context->game->mindbugExpansion->canGetExtraTurn()) {
            if ($activePlayerId != $context->currentPlayerId) {
                $context->game->setGameStateValue(FRENZY_EXTRA_TURN_FOR_OPPORTUNIST, $context->currentPlayerId);
                $context->game->setGameStateValue(PLAYER_BEFORE_FRENZY_EXTRA_TURN_FOR_OPPORTUNIST, $activePlayerId);
            } else {
                $context->game->setGameStateValue(FRENZY_EXTRA_TURN, 1);
            }
        }
    }
}

?>
