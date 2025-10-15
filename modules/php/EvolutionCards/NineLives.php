<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;

class NineLives extends EvolutionCard {
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
    }


    function applyEffect(Context $context) {
        $context->game->playEvolutionToTable($context->currentPlayerId, $this, '');

        $context->game->applyResurrectCard(
            $context->currentPlayerId, 
            $this, 
            clienttranslate('${player_name} reached 0 [Heart]. With ${card_name}, all [Energy], [Star], cards and Evolutions are lost but player gets back 9[Heart] and 9[Star]'),
            false, 
            true,
            true,
            9,
            9
        );
    }
}
