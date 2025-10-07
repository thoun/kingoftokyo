<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\GameFramework\NotificationMessage;
use Bga\Games\KingOfTokyo\Objects\Context;

class StatueOfLiberty extends PowerCard
{
    public function incDieRollCount(Context $context) {
        return 1;
    }
}
