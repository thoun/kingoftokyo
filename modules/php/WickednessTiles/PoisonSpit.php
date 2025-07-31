<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\WickednessTiles;

use Bga\Games\KingOfTokyo\Objects\AddSmashTokens;
use Bga\Games\KingOfTokyo\WickednessTiles\WickednessTile;
use Bga\Games\KingOfTokyo\Objects\Context;

class PoisonSpit extends WickednessTile {
    public function addSmashTokens(Context $context): AddSmashTokens {
        return new addSmashTokens(poison: 1);
    }
}

?>