<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\GameFramework\NotificationMessage;
use Bga\Games\KingOfTokyo\Objects\Context;

class Hibernation extends PowerCard {
    public function getUnmetConditionRequirement(Context $context): ?NotificationMessage {
        if ($context->currentPlayerInTokyo) {
            return new NotificationMessage(clienttranslate('You CANNOT buy this card while in TOKYO'));
        }
        return null;
    }
}
