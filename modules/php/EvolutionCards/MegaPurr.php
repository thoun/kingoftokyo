<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;

class MegaPurr extends EvolutionCard {
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
    }

    public function immediateEffect(Context $context) {
        $otherPlayers = Arrays::filter($context->game->getOtherPlayers($context->currentPlayerId), fn($player) => $player->energy > 0 || $player->score > 0);
        $context->game->applyGiveSymbolQuestion($context->currentPlayerId, $this, $otherPlayers, [5, 0]);
    }
}

?>