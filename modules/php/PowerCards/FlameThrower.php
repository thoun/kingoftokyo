<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

class FlameThrower extends PowerCard {
    public function immediateEffect(Context $context) {
        $otherPlayersIds = $context->game->getOtherPlayersIds($context->currentPlayerId);
        $damages = [];
        foreach ($otherPlayersIds as $otherPlayerId) {
            $damages[] = new Damage($otherPlayerId, 2, $context->currentPlayerId, $this);
        }
        return $damages;
    }
}

?>
