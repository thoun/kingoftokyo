<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\CurseCards;

use Bga\Games\KingOfTokyo\CurseCards\CurseCard;
use Bga\Games\KingOfTokyo\Objects\Context;

class KhepriRebellion extends CurseCard {

    public function applyAnkhEffect(Context $context) {
        return ST_PLAYER_GIVE_GOLDEN_SCARAB;
    }

    public function applySnakeEffect(Context $context) {
        $context->game->anubisExpansion->changeGoldenScarabOwner($context->currentPlayerId);
    }
}

?>