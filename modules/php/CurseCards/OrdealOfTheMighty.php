<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\CurseCards;

use Bga\Games\KingOfTokyo\CurseCards\CurseCard;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

class OrdealOfTheMighty extends CurseCard {

    public function applyAnkhEffect(Context $context) {
        $context->game->applyGetHealth($context->currentPlayerId, 2, $this, $context->currentPlayerId);
    }

    public function applySnakeEffect(Context $context) {
        $playersIds = $context->game->getPlayersIdsWithMaxColumn('player_health');
        $damages = [];
        foreach ($playersIds as $pId) {
            $damages[] = new Damage($pId, 1, 0, $this);
        }
        return $damages;
    }
}

?>