<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\WickednessTiles;

use Bga\Games\KingOfTokyo\WickednessTiles\WickednessTile;
use Bga\Games\KingOfTokyo\Objects\Context;

class Barbs extends WickednessTile {
    public function immediateEffect(Context $context) {
        $diceCounts = $context->game->getGlobalVariable(DICE_COUNTS, true);
        if ($diceCounts[6] >= 2) {
            $diceCounts[6] += 1;
            $context->game->setGlobalVariable(DICE_COUNTS, $diceCounts);
        }
    }

    public function addSmashesOrder(): int {
        return 1;
    }

    public function addSmashes(Context $context): int {
        if ($context->dieSmashes + $context->addedSmashes >= 2) {
            return 1;
        }
        return 0;
    }
}

?>