<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\CurseCards;

use Bga\Games\KingOfTokyo\CurseCards\CurseCard;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

class InadequateOffering extends CurseCard {

    public function applyAnkhEffect(Context $context) {
        $context->game->drawCard($context->currentPlayerId, ST_RESOLVE_DICE);
        return -1;
    }

    public function applySnakeEffect(Context $context) {
        return $context->game->anubisExpansion->snakeEffectDiscardKeepCard($context->currentPlayerId);
    }
}

?>