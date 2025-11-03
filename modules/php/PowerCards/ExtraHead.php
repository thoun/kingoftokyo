<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class ExtraHead extends PowerCard
{
    public function incDieCount(Context $context) {
        return 1;
    }
}
