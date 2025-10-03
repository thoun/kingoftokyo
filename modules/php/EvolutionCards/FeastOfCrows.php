<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;

class FeastOfCrows extends EvolutionCard {
    public function immediateEffect(Context $context) {
        $otherPlayers = array_filter($context->game->getOtherPlayers($context->currentPlayerId), fn($player) => $player->energy > 0 || $player->score > 0 || $player->health > 0);
        $context->game->applyGiveSymbolQuestion($context->currentPlayerId, $this, $otherPlayers, [4, 0, 5]);
    }
}
