<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class NoBrain extends PowerCard
{
    public function addSmashes(Context $context): int {
        return 1;
    }
}
