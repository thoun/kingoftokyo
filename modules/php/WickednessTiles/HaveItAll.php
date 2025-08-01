<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\WickednessTiles;

use Bga\Games\KingOfTokyo\WickednessTiles\WickednessTile;
use Bga\Games\KingOfTokyo\Objects\Context;

class HaveItAll extends WickednessTile {
    public function immediateEffect(Context $context) {
        $playerId = $context->currentPlayerId;

        $cardsOfPlayer = $context->game->getCardsFromDb($context->game->cards->getCardsInLocation('hand', $playerId));
        $keepCardsCount = count(array_filter($cardsOfPlayer, fn($card) => $card->type < 100));
        $context->game->applyGetPoints($playerId, $keepCardsCount, $this);
    }

    public function buyCardEffect(Context $context) {
        $playerId = $context->currentPlayerId;

        $context->game->applyGetPoints($playerId, 1, $this);
    }
}

?>