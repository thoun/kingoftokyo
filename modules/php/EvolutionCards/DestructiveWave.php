<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

class DestructiveWave extends EvolutionCard {
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
    }

    public function immediateEffect(Context $context) {
        $otherPlayersIds = $context->game->getOtherPlayersIds($context->currentPlayerId);
        $damages = [];
        foreach ($otherPlayersIds as $otherPlayerId) {
            $damages[] = new Damage($otherPlayerId, 2, $context->currentPlayerId, $this);
        }
        return $damages;
    }
}
