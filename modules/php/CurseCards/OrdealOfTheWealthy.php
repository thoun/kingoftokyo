<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\CurseCards;

use Bga\Games\KingOfTokyo\CurseCards\CurseCard;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

class OrdealOfTheWealthy extends CurseCard {

    public function applyAnkhEffect(Context $context) {
        $context->game->applyGetPoints($context->currentPlayerId, 2, $this);
    }

    public function applySnakeEffect(Context $context) {
        $playersIds = $context->game->getPlayersIdsWithMaxColumn('player_score');
        foreach ($playersIds as $pId) {
            $context->game->applyLosePoints($pId, 1, $this);
        }
    }
}

?>