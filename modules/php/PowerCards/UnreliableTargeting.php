<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class UnreliableTargeting extends PowerCard
{
    public function __construct() {
        $this->mindbugKeywords = [HUNTER];
    }

    public function applyEffect(Context $context) {
        if ($context->game->getPlayer($context->targetPlayerId)->eliminated) {
            $context->game->removeCard($context->currentPlayerId, $this);
        } else {
            $this->activated = null;
            $context->game->powerCards->updateCard($this, 'activated');
            $context->game->powerCards->moveCard($this, 'hand', $context->targetPlayerId);

            $context->game->notify->all("buyCard", clienttranslate('${player_name2} gives ${card_name} to ${player_name}'), [
                'playerId' => $context->targetPlayerId,
                'player_name' => $context->game->getPlayerNameById($context->targetPlayerId),
                'mindbuggedPlayerId' => $context->currentPlayerId,
                'player_name2' => $context->game->getPlayerNameById($context->currentPlayerId),
                'card' => $this,
                'card_name' => UNRELIABLE_TARGETING_CARD,
            ]);
        }

    }
}
