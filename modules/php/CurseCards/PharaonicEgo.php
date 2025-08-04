<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\CurseCards;

use Bga\Games\KingOfTokyo\CurseCards\CurseCard;
use Bga\Games\KingOfTokyo\Objects\Context;

class PharaonicEgo extends CurseCard {
    public function applySnakeEffect(Context $context) {
        $context->game->replacePlayersInTokyo($context->currentPlayerId);
    }
}

?>