<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\CurseCards;

use Bga\Games\KingOfTokyo\CurseCards\CurseCard;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

class RagingFlood extends CurseCard {

    public function applyAnkhEffect(Context $context) {
        $context->game->setGameStateValue(RAGING_FLOOD_EXTRA_DIE, 1);
        return ST_PLAYER_SELECT_EXTRA_DIE;
    }

    public function applySnakeEffect(Context $context) {
        return ST_PLAYER_DISCARD_DIE;
    }
}

?>