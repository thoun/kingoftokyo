<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;

class SonOfKongKiko extends EvolutionCard {
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
    }


    function applyEffect(Context $context) {
        $context->game->playEvolutionToTable($context->currentPlayerId, $this, '');
        $context->game->removeEvolution($context->currentPlayerId, $this, false, 5000);

        $context->game->applyResurrectCard(
            $context->currentPlayerId, 
            $this, 
            /*client TODOPUKK translate*/('${player_name} reached 0 [Heart]. With ${card_name}, ${player_name} gets back to 4[Heart], leave Tokyo, and continue playing'),
            false, 
            false,
            false,
            4,
            null
        );
    }
}
