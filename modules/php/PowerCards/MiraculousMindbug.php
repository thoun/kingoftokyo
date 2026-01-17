<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\GameFramework\NotificationMessage;
use Bga\Games\KingOfTokyo\Objects\Context;

class MiraculousMindbug extends PowerCard
{
    public function getUnmetConditionRequirement(Context $context): ?NotificationMessage {
        if ($context->game->getPlayerHealth($context->currentPlayerId) > 3) {
            return new NotificationMessage(clienttranslate('You can only buy this card if you have 3[Heart] or fewer.'));
        }
        return null;
    }

    public function immediateEffect(Context $context) {
        $playerId = $context->currentPlayerId;
        $playerName = $context->game->getPlayerNameById($playerId);

        $points = 0;
        // go back to $points stars
        $context->game->DbQuery("UPDATE player SET `player_score` = $points where `player_id` = $playerId");
        $context->game->notify->all('points','', [
            'playerId' => $playerId,
            'player_name' => $playerName,
            'points' => $points,
        ]);

        $newHearts = 10;
        // get back to $newHearts heart
        $context->game->DbQuery("UPDATE player SET `player_health` = $newHearts where `player_id` = $playerId");
        $context->game->notify->all('health', '', [
            'playerId' => $playerId,
            'player_name' => $playerName,
            'health' => $newHearts,
        ]);

        $context->game->mindbugExpansion->applyGetMindbugTokens($context->currentPlayerId, 1, $this);
    }


}
