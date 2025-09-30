<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class EvacuationOrder extends PowerCard {
    public function immediateEffect(Context $context) {
        $otherPlayersIds = $context->game->getOtherPlayersIds($context->currentPlayerId);
        foreach ($otherPlayersIds as $otherPlayerId) {
            $context->game->applyLosePoints($otherPlayerId, 5, $this);
        }
    }
}

?>
