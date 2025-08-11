<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\CurseCards;

use Bga\Games\KingOfTokyo\CurseCards\CurseCard;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

class OrdealOfTheSpiritual extends CurseCard {

    public function applyAnkhEffect(Context $context) {
        $context->game->applyGetEnergy($context->currentPlayerId, 2, $this);
    }

    public function applySnakeEffect(Context $context) {
        $playersIds = $context->game->getPlayersIdsWithMaxColumn('player_energy');
        foreach ($playersIds as $pId) {
            $context->game->applyLoseEnergy($pId, 1, $this);
        }
    }
}

?>