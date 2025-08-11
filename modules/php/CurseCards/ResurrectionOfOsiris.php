<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\CurseCards;

use Bga\Games\KingOfTokyo\CurseCards\CurseCard;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

class ResurrectionOfOsiris extends CurseCard {

    public function applyAnkhEffect(Context $context) {
        $context->game->replacePlayersInTokyo($context->currentPlayerId);
    }

    public function applySnakeEffect(Context $context) {
        $context->game->leaveTokyo($context->currentPlayerId);
    }
}

?>