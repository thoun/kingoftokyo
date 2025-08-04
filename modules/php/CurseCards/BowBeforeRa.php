<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\CurseCards;

use Bga\Games\KingOfTokyo\CurseCards\CurseCard;
use Bga\Games\KingOfTokyo\Objects\Context;

class BowBeforeRa extends CurseCard {
    public function immediateEffect(Context $context) {
        $context->game->changeAllPlayersMaxHealth();
    }
}

?>