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
        if ($nextState === 'next' && count($intervention->remainingPlayersId) > 0) {
            array_shift($intervention->remainingPlayersId); 
        }
        if ($endState != null) {
            $intervention->endState = $endState;
        }

        $this->setGlobalVariable($interventionName, $intervention);
    }

    function stIntervention(string $interventionName) {
        $intervention = $this->getGlobalVariable($interventionName);

        $keep = ($intervention->nextState === 'keep' || $intervention->nextState === 'next') && count($intervention->remainingPlayersId) > 0 && $this->getPlayerHealth($intervention->remainingPlayersId[0]) > 0;
        
        if ($keep) { // current player continues / next (intervention player) / or leaving transition
            $this->gamestate->setPlayersMultiactive([$intervention->remainingPlayersId[0]], 'transitionError', true);
        } else { // leaving transition
            $this->deleteGlobalVariable($interventionName);
            if (gettype($intervention->endState) == 'string') {
                $this->gamestate->nextState($intervention->endState);
            } else if (gettype($intervention->endState) == 'integer') {
                $this->jumpToState($intervention->endState);
            } else {
                throw new \Error('Invalid endState');
            }
        }
    }
}
