<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\CurseCards;

use Bga\Games\KingOfTokyo\CurseCards\CurseCard;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

class HotepPeace extends CurseCard {

    public function applyAnkhEffect(Context $context) {
        $context->game->changeGoldenScarabOwner($context->currentPlayerId);
    }

    public function applySnakeEffect(Context $context) {
        $dice = $context->game->getPlayerRolledDice($context->currentPlayerId, true, false, false);
        $diceCounts = $context->game->getRolledDiceCounts($context->currentPlayerId, $dice, true);
        $rolledSmashes = $diceCounts[6];
        if ($rolledSmashes > 0) {
            $context->game->applyLoseEnergy($context->currentPlayerId, $rolledSmashes, $this);
        }
    }
}

?>