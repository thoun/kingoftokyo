<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class AcidAttack extends PowerCard
{

    public function addSmashesOrder(): int {
        return 1;
    }

    public function addSmashes(Context $context): int {
        return 1;
    }
}
