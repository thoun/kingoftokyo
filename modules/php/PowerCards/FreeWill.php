<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\GameFramework\NotificationMessage;
use Bga\Games\KingOfTokyo\Objects\Context;

class FreeWill extends PowerCard
{
    public function getUnmetConditionRequirement(Context $context): ?NotificationMessage {
        if ($context->game->mindbugExpansion->mindbugTokens->get($context->currentPlayerId) < 0) {
            return new NotificationMessage(/*clienttranslateTODOMB*/('You cannot buy this card if you have a Mindbug Token.'));
        }
        return null;
    }
}
