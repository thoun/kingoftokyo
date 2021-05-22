<?php

namespace KOT\States;

trait InterventionTrait {

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////

    function setInterventionNextState(string $interventionName, string $nextState, $endState = null, object $intervention = null) {
        if ($intervention == null) {
            $intervention = $this->getGlobalVariable($interventionName);
        }

        $intervention->nextState = $nextState;
        if ($endState != null) {
            $intervention->endState = $endState;
        }

        $this->setGlobalVariable($interventionName, $intervention);
    }

    function stIntervention(string $interventionName) {

        $intervention = $this->getGlobalVariable($interventionName);
        $intervention->currentPlayerId = $intervention->remainingPlayersId[0];

        if ($intervention->nextState === 'keep') { // current player continues / next (intervention player) / or leaving transition
            $this->gamestate->setPlayersMultiactive([$intervention->remainingPlayersId[0]], 'transitionError', true);
        } else if ($intervention->nextState === 'next' && count($intervention->remainingPlayersId) > 1) { // next intervention player
            array_shift($intervention->remainingPlayersId); 
            $this->setGlobalVariable($interventionName, $intervention);
            $this->gamestate->setPlayersMultiactive([$intervention->remainingPlayersId[0]], 'transitionError', true);
        } else { // leaving transition
            $this->deleteGlobalVariable($interventionName);
            if (gettype($intervention->endState) == 'string') {
                $this->gamestate->nextState($intervention->endState);
            } else if (gettype($intervention->endState) == 'integer') {
                $this->gamestate->jumpToState($intervention->endState);
            } else {
                throw new \Error('invalide endState');
            }
        }
    }
}
