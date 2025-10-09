<?php

namespace KOT\States;

require_once(__DIR__.'/../Objects/player-intervention.php');

use KOT\Objects\CancelDamageIntervention;

trait CardsStateTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stOpportunistChooseMimicCard() {
        $intervention = $this->getGlobalVariable(OPPORTUNIST_INTERVENTION);

        $this->gamestate->setPlayersMultiactive([$intervention->remainingPlayersId[0]], 'stay', true);
    }

    function stCancelDamage() {            
        $intervention = $this->getDamageIntervention();

        if ($intervention === null) {
            throw new \Exception('No damage informations found');
            return;
        }

        //$this->resolveRemainingDamages($intervention, false, true);
        
        $this->gamestate->setPlayersMultiactive([$intervention->remainingPlayersId[0]], 'stay', true);
    }

}
