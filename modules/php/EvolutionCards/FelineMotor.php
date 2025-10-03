<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;

class FelineMotor extends EvolutionCard {
    public function immediateEffect(Context $context) {
        $startedTurnInTokyo = $context->game->getGlobalVariable(STARTED_TURN_IN_TOKYO, true);
        if (in_array($context->currentPlayerId, $startedTurnInTokyo)) {
            throw new \BgaUserException(clienttranslate("You started your turn in Tokyo"));
        }
        
        $context->game->moveToTokyoFreeSpot($context->currentPlayerId);
        $context->game->setGameStateValue(PREVENT_ENTER_TOKYO, 1);
        $context->game->goToState($context->game->redirectAfterHalfMovePhase());
    }
}
