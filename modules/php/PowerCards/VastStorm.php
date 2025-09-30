<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class VastStorm extends PowerCard {
    public function immediateEffect(Context $context) {
        $context->game->applyGetPoints($context->currentPlayerId, 2, $this);
        $otherPlayersIds = $context->game->getOtherPlayersIds($context->currentPlayerId);
        foreach ($otherPlayersIds as $otherPlayerId) {
            $energy = $context->game->getPlayerEnergy($otherPlayerId);
            $lostEnergy = (int)floor($energy / 2);
            $context->game->applyLoseEnergy($otherPlayerId, $lostEnergy, $this);
        }
    }
}

?>
