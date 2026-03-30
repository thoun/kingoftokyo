<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;

class NurtureTheYoung extends EvolutionCard {
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
    }

    public function immediateEffect(Context $context) {
        $currentPlayerScore = $context->game->bga->playerScore->get($context->currentPlayerId);
        $dbResults = $context->game->getCollectionFromDb("SELECT `player_id` FROM `player` WHERE `player_score` > $currentPlayerScore");
        $playersIdsWithMorePoints = array_map(fn($dbResult) => intval($dbResult['player_id']), array_values($dbResults));
        foreach ($playersIdsWithMorePoints as $playerIdWithMorePoints) {
            $context->game->applyGiveSymbols([0], $playerIdWithMorePoints, $context->currentPlayerId, $this);
        }
    }
}
