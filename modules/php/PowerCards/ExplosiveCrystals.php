<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

class ExplosiveCrystals extends PowerCard
{
    public function __construct() {
        $this->mindbugKeywords = [POISON];
    }

    public function applyEffect(Context $context) {
        $damages = [];

        if ($context->game->getPlayer($context->currentPlayerId)->eliminated) {
            $damages[] = new Damage($context->attackerPlayerId, $context->lostHearts * 2, $context->currentPlayerId, $this);
        }

        $context->game->removeCard($context->currentPlayerId, $this);

        return $damages;
    }
}
