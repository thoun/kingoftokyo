<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\Objects\Context;

class ScaryFace extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = PERMANENT;
    }

    public function applyEffect(Context $context) {
        $dice = $context->game->getPlayerRolledDice($context->currentPlayerId, false, false, false);
        $diceCounts = $context->game->getRolledDiceCounts($context->currentPlayerId, $dice, false);

        if ($diceCounts[4] === 3) {
            $context->game->applyGetHealth($context->currentPlayerId, 3, $this, $context->currentPlayerId);
            $playersIds = $context->game->getPlayersIdsWithMaxColumn('player_score');
            $playersIds = Arrays::filter($playersIds, fn($playerId) => $playerId != $context->currentPlayerId);
            if (count($playersIds) > 0) {
                $targetPlayerId = $playersIds[bga_rand(0, count($playersIds) - 1)];
                $context->game->applyLosePoints($targetPlayerId, 1, $this);
                $context->game->applyGetPoints($context->currentPlayerId, 1, $this);
            }
        }
    }
}
