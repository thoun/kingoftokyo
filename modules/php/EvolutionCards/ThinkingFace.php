<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\Objects\Context;

class ThinkingFace extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
    }

    public function applyEffect(Context $context) {
        $dice = $context->game->getPlayerRolledDice($context->currentPlayerId, false, false, false);
        $diceCounts = $context->game->getRolledDiceCounts($context->currentPlayerId, $dice, false);

        if ($diceCounts[4] === 3) {
            $context->game->applyGetHealth($context->currentPlayerId, 3, $this, $context->currentPlayerId);
            $playersIds = $context->game->getPlayersIdsWithMinColumn('player_health');
            // TODO let the player chose the one to heal
            $secondHealId = in_array($context->currentPlayerId, $playersIds) ? $context->currentPlayerId : $playersIds[bga_rand(0, count($playersIds) - 1)];
            $context->game->applyGetHealth($secondHealId, 1, $this, $context->currentPlayerId);
        }
    }
}
