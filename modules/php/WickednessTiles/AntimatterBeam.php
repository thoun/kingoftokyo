<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\WickednessTiles;

use Bga\Games\KingOfTokyo\WickednessTiles\WickednessTile;
use Bga\Games\KingOfTokyo\Objects\Context;

class AntimatterBeam extends WickednessTile {
    public function immediateEffect(Context $context) {
        $diceCounts = $context->game->getGlobalVariable(DICE_COUNTS, true);
        $diceCounts[6] *= 2;
        $context->game->setGlobalVariable(DICE_COUNTS, $diceCounts);
    }

    public function addSmashesOrder(): int {
        return 2;
    }

    public function addSmashes(Context $context): int {
        return $context->dieSmashes + $context->addedSmashes;
    }
}

?>