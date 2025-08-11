<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\CurseCards;

use Bga\Games\KingOfTokyo\CurseCards\CurseCard;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

class VengeanceOfHorus extends CurseCard {

    public function applyAnkhEffect(Context $context) {
        $dice = $context->game->getPlayerRolledDice($context->currentPlayerId, true, false, false);
        $diceCounts = $context->game->getRolledDiceCounts($context->currentPlayerId, $dice, true);
        $rolledSmashes = $diceCounts[6];
        if ($rolledSmashes > 0) {
            $context->game->applyGetPoints($context->currentPlayerId, $rolledSmashes, $this);
        }
    }

    public function applySnakeEffect(Context $context) {
        $dice = $context->game->getPlayerRolledDice($context->currentPlayerId, true, false, false);
        $diceCounts = $context->game->getRolledDiceCounts($context->currentPlayerId, $dice, true);
        $rolledSmashes = $diceCounts[6];
        if ($rolledSmashes > 0) {
            return [new Damage($context->currentPlayerId, $rolledSmashes, 0, $this)];
        } else {
            return null;
        }
    }
}

?>