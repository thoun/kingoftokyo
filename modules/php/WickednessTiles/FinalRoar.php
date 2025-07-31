<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\WickednessTiles;

use Bga\Games\KingOfTokyo\WickednessTiles\WickednessTile;
use Bga\Games\KingOfTokyo\Objects\Context;

class FinalRoar extends WickednessTile {
    public function winOnElimination(Context $context): bool {
        return $context->game->getPlayerScore($context->currentPlayerId) >= 16;
    }

    public function onTrigger(Context $context) {
        $context->game->applyGetPointsIgnoreCards($context->currentPlayerId, WIN_GAME, 0);
            
        $context->game->notify->all("log", clienttranslate('${player_name} is eliminated with ${points} [Star] or more and wins the game with ${card_name}'), [
            'playerId' => $context->currentPlayerId,
            'player_name' => $context->game->getPlayerNameById($context->currentPlayerId),
            'card_name' => 2000 + $this->type,
            'points' => 16,
        ]);
    }
}

?>