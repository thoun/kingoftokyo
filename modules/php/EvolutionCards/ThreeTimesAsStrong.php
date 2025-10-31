<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\GameFrameworkPrototype\Helpers\Arrays;
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

        $dice = $context->game->getPlayerRolledDice($context->currentPlayerId, false, false, false);
        $diceCounts = $context->game->getRolledDiceCounts($context->currentPlayerId, $dice, false);

        if (Arrays::some($diceCounts, fn($diceCount) => $diceCount === 3)) {
            $otherPlayerIds = $context->game->getOtherPlayersIds($context->currentPlayerId);
            foreach ($otherPlayerIds as $otherPlayerId) {
                $damages[] = new Damage($otherPlayerId, 1, $context->currentPlayerId, $this);
            }

        }
        return $damages;
    }
}
