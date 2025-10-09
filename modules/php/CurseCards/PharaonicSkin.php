<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\CurseCards;

use Bga\Games\KingOfTokyo\CurseCards\CurseCard;
use Bga\Games\KingOfTokyo\Objects\Context;

class PharaonicSkin extends CurseCard {

    public function applyAnkhEffect(Context $context) {
        $context->game->anubisExpansion->changeGoldenScarabOwner($context->currentPlayerId);
    }

    public function applySnakeEffect(Context $context) {
        $playerIdWithGoldenScarab = $context->game->anubisExpansion->getPlayerIdWithGoldenScarab();
        if ($playerIdWithGoldenScarab != null && $context->currentPlayerId != $playerIdWithGoldenScarab && count($context->game->anubisExpansion->argGiveSymbols((int)$context->game->getActivePlayerId())['combinations']) > 0) {
            return ST_PLAYER_GIVE_SYMBOLS;
        }
    }
}

?>