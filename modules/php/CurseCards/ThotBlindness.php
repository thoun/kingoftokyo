<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\CurseCards;

use Bga\Games\KingOfTokyo\CurseCards\CurseCard;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

class ThotBlindness extends CurseCard {

    public function applyAnkhEffect(Context $context) {
        $context->game->changeGoldenScarabOwner($context->currentPlayerId);
    }

    public function applySnakeEffect(Context $context) {
         $context->game->applyLoseEnergy($context->currentPlayerId, 2, $this);
    }
}

?>