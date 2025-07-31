<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\WickednessTiles;

use Bga\Games\KingOfTokyo\WickednessTiles\WickednessTile;
use Bga\Games\KingOfTokyo\Objects\Context;

class EvilLair extends WickednessTile {
    public function incPowerCardsReduction(Context $context) {
        return 1;
    }
}

?>