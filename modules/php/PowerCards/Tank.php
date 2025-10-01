<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

class Tank extends PowerCard {
    public function immediateEffect(Context $context) {
        $context->game->applyGetPoints($context->currentPlayerId, 4, $this);
        return [new Damage($context->currentPlayerId, 3, $context->currentPlayerId, $this)];
    }
}

?>
