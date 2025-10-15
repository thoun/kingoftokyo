<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

class ThreeTimesAsStrong extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = PERMANENT;
    }

    public function applyEffect(Context $context) {
        $damages = [];
        if (true) { // TODOMB
            $otherPlayerIds = $context->game->getOtherPlayersIds($context->currentPlayerId);
            foreach ($otherPlayerIds as $otherPlayerId) {
                return new Damage($otherPlayerId, 1, $context->currentPlayerId, $this);
            }

        }
        return $damages; // TODOMB test
    }
}
