<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;

class WorstNightmare extends EvolutionCard {
    public function __construct()
    {
        $this->evolutionType = GIFT;
    }

    function applyEffect(Context $context) {
        $givers = Arrays::filter([$context->game->getPlayer($context->currentPlayerId)], fn($player) => $player->energy > 0 || $player->health > 0);

        if (count($givers) == 0) {
            return false;
        }
        $receiving = $this->ownerId;
        $context->game->applyGiveEnergyOrLoseHeartsQuestion($receiving, $givers, $this, 1);
        return true;
    }

}
