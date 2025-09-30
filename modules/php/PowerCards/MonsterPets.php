<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class MonsterPets extends PowerCard {
    public function immediateEffect(Context $context) {
        $playersIds = $context->game->getPlayersIds();
        foreach ($playersIds as $pId) {
            $context->game->applyLosePoints($pId, 3, $this);
        }
    }
}

?>
