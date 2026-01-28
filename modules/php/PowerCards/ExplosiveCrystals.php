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

        return $damages;
    }

    public function onPlayerEliminated(Context $context) {
        if (!$this->activated) {
            return;
        }
        $context->game->removeCard($context->currentPlayerId, $this);

        $damages = $context->game->getObjectListFromDB( "SELECT `from`, `damages` FROM `turn_damages` WHERE `to` = $context->currentPlayerId");
        if (count($damages) === 0) {
            return null;
        }
        $lastDamage = $damages[count($damages) - 1];
        $from = (int)$lastDamage['from'];
        $damage = (int)$lastDamage['damages'];
        if ($from === $context->currentPlayerId) {
            return null;
        }

        return [new Damage($from, $damage * 2, $context->currentPlayerId, $this)];
    }
}
