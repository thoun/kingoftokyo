<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

class GasRefinery extends PowerCard {
    public function immediateEffect(Context $context) {
        $context->game->applyGetPoints($context->currentPlayerId, 2, $this);
        $otherPlayersIds = $context->game->getOtherPlayersIds($context->currentPlayerId);
        $damages = [];
        foreach ($otherPlayersIds as $otherPlayerId) {
            $damages[] = new Damage($otherPlayerId, 3, $context->currentPlayerId, $this);
        }
        return $damages;
    }
}

?>
