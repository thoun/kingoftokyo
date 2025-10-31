<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

class OffensiveProtocol extends PowerCard
{
    public function __construct() {
        $this->mindbugKeywords = [SNEAKY];
    }

    public function applyEffect(Context $context) {
        $otherPlayersIds = $context->game->getOtherPlayersIds($context->currentPlayerId);
        $damages = [];
        foreach ($otherPlayersIds as $otherPlayerId) {
            $damages[] = new Damage($otherPlayerId, $context->lostPoints, $context->currentPlayerId, $this);
        }

        $context->game->removeCard($context->currentPlayerId, $this);
        return $damages;
    }
}
