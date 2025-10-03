<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

class SmashingPumpkin extends EvolutionCard {
    public function immediateEffect(Context $context) {
        $players = $context->game->getPlayers();
        $damages = [];
        foreach ($players as $player) {
            if ($player->score >= 12) {
                $damages[] = new Damage($player->id, 2, $context->currentPlayerId, $this);
            }
        }
        return $damages;
    }
}
