<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class Burrowing extends PowerCard
{
    public function addSmashes(Context $context): int {
        return $context->currentPlayerInTokyo ? 1 : 0;
    }
}
