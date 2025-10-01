<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

class HighAltitudeBombing extends PowerCard {
    public function immediateEffect(Context $context) {
        $playersIds = $context->game->getPlayersIds();
        $damages = [];
        foreach ($playersIds as $pId) {
            $damages[] = new Damage($pId, 3, $context->currentPlayerId, $this);
        }
        return $damages;
    }
}

?>
