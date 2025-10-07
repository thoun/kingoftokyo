<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class GiantBrain extends PowerCard
{
    public function incDieRollCount(Context $context) {
        return 1;
    }
}
