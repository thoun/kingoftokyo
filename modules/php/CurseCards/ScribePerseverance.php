<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\CurseCards;

use Bga\Games\KingOfTokyo\CurseCards\CurseCard;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

class ScribePerseverance extends CurseCard {

    public function applyAnkhEffect(Context $context) {
        $dice = $context->game->getPlayerRolledDice($context->currentPlayerId, true, false, false);
        $diceCounts = $context->game->getRolledDiceCounts($context->currentPlayerId, $dice, true);
        $rolled1s = $diceCounts[1];
        if ($rolled1s > 0) {
            $context->game->applyGetEnergy($context->currentPlayerId, $rolled1s, $this);
        }
    }

    public function applySnakeEffect(Context $context) {
        $first1die = $context->game->getFirstDieOfValue($context->currentPlayerId, 1);
        if ($first1die != null) {
            $context->game->anubisExpansion->applyDiscardDie($first1die->id);
        }
    }
}

?>