<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\GameFramework\UserException;
use Bga\Games\KingOfTokyo\Objects\Context;

class HelpfulMindbug extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
    }
    
    public function immediateEffect(Context $context) {
        $activePlayerId = (int)$context->game->getActivePlayerId();
        if($context->currentPlayerId === $activePlayerId) {
            throw new UserException('You cannot use this Evolution on yourself');
        }

        $diceCounts = $context->game->getGlobalVariable(\DICE_COUNTS, true);
        $context->game->applyGetHealth($context->currentPlayerId, $diceCounts[4], $this, $context->currentPlayerId);
        $context->game->applyGetEnergy($context->currentPlayerId, $diceCounts[5], $this);
    }
}
