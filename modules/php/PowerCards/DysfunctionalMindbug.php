<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

class DysfunctionalMindbug extends PowerCard
{
    public function immediateEffect(Context $context) {
        $playersIds = $context->game->getPlayersIds();
        $damages = [];
        foreach ($playersIds as $playerId) {
            if ($context->game->mindbugExpansion->mindbugTokens->get($playerId) > 0) {
                $damages[] = new Damage($playerId, 3, $context->currentPlayerId, $this);
            }
        }
        return $damages;
    }
}
