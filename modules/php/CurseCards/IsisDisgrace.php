<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\CurseCards;

use Bga\Games\KingOfTokyo\CurseCards\CurseCard;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

class IsisDisgrace extends CurseCard {

    public function applyAnkhEffect(Context $context) {
        $context->game->anubisExpansion->changeGoldenScarabOwner($context->currentPlayerId);
    }

    public function applySnakeEffect(Context $context) {
        return [new Damage($context->currentPlayerId, 1, 0, $this)];
    }
}

?>