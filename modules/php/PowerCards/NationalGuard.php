<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

class NationalGuard extends PowerCard {
    public function immediateEffect(Context $context): void {
        $context->game->applyGetPoints($context->currentPlayerId, 2, $this);
        $context->game->addDamageToResolve(new Damage($context->currentPlayerId, 2, $context->currentPlayerId, $this));
    }
}

?>
