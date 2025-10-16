<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

use const Bga\Games\KingOfTokyo\PowerCards\POISON;

class PoisonedTentacles extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = PERMANENT;
        $this->mindbugKeywords = [POISON];
    }

    public function applyEffect(Context $context) {
        $damages = [];
        if ($context->lostHearts >= 3) {
            $otherPlayerIds = $context->game->getOtherPlayersIds($context->currentPlayerId);
            foreach ($otherPlayerIds as $otherPlayerId) {
                return new Damage($otherPlayerId, 1, $context->currentPlayerId, $this);
            }
        }

        $context->game->removeEvolution($context->currentPlayerId, $this);

        return $damages; // TODOMB test
    }
}
