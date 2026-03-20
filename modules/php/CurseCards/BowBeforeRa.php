<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\CurseCards;

use Bga\Games\KingOfTokyo\CurseCards\CurseCard;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

class BowBeforeRa extends CurseCard {
    public function immediateEffect(Context $context) {
        $context->game->changeAllPlayersMaxHealth();
    }

    public function applyAnkhEffect(Context $context) {
        $context->game->applyGetHealth($context->currentPlayerId, 2, $this, $context->currentPlayerId);
    }

    public function applySnakeEffect(Context $context): void {
        $context->game->addDamageToResolve(new Damage($context->currentPlayerId, 2, 0, $this));
    }
}

?>