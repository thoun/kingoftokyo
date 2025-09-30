<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\CurseCards;

use Bga\Games\KingOfTokyo\CurseCards\CurseCard;
use Bga\Games\KingOfTokyo\Objects\Context;

class BuildersUprising extends CurseCard {

    public function applyAnkhEffect(Context $context) {
        if (!$context->game->inTokyo($context->currentPlayerId) && $context->game->mindbugExpansion->canGetExtraTurn()) {
            $context->game->setGameStateValue(BUILDERS_UPRISING_EXTRA_TURN, 1);
        }
    }

    public function applySnakeEffect(Context $context) {
        $context->game->applyLosePoints($context->currentPlayerId, 2, $this);
    }
}

?>