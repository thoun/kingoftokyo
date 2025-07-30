<?php

namespace KOT\States;

require_once(__DIR__.'/Objects/damage.php');

use KOT\Objects\Damage;

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

        $keep = ($intervention->nextState === 'keep' || $intervention->nextState === 'next') 
            && count($intervention->remainingPlayersId) > 0
            && !$this->getPlayer($intervention->remainingPlayersId[0])->eliminated;
        
        if ($keep) { // current player continues / next (intervention player) / or leaving transition
            $this->gamestate->setPlayersMultiactive([$intervention->remainingPlayersId[0]], 'transitionError', true);
        } else { // leaving transition
            $this->deleteGlobalVariable($interventionName);
            if (gettype($intervention->endState) == 'string') {
                $this->gamestate->nextState($intervention->endState);
            } else if (gettype($intervention->endState) == 'integer') {
                $this->goToState($intervention->endState);
            } else {
                throw new \Error('Invalid endState');
            }
        }
    }

    function reduceInterventionDamages(int $playerId, &$intervention, int $reduceBy /* -1 for remove all for player*/) {
        $damageIndex = $this->array_find_index($intervention->damages, fn($d) => $d->playerId == $playerId);
        $allDamageIndex = $this->array_find_index($intervention->allDamages, fn($d) => $d->playerId == $playerId);

        if ($reduceBy === -1 || $reduceBy >= $intervention->damages[$damageIndex]->remainingDamage) {
            // damage is fully cancelled, we remove it
            array_splice($intervention->damages, $damageIndex, 1);
            array_splice($intervention->allDamages, $allDamageIndex, 1);
        } else {
            $intervention->damages[$damageIndex]->damage -= $reduceBy;
            $intervention->damages[$damageIndex]->remainingDamage -= $reduceBy;
            $intervention->allDamages[$allDamageIndex]->remainingDamage -= $reduceBy;
        }
        
        $this->setDamageIntervention($intervention);
    }

    function reduceInterventionDamagesForArray(int $playerId, array $damages, int $reduceBy): array {
        $newDamages = [];

        foreach($damages as $damage) {
            if ($damage->playerId == $playerId && $reduceBy > 0) {
                if ($reduceBy >= $damage->damage) {
                    $reduceBy -= $damage->damage;
                } else {
                    $newDamage = Damage::clone($damage);
                    $newDamage->damage -= $reduceBy;
                    $newDamages[] = $newDamage;
                    $reduceBy = 0;
                }
            } else {
                $newDamages[] = $damage;
            }
        }
        return $newDamages;
    }

    function createRemainingDamage(int $playerId, array $damages): ?Damage {
        $damageNumber = 0;
        $damageDealerId = 0;
        $cardType = 0;
        $giveShrinkRayToken = 0;
        $givePoisonSpitToken = 0;
        $smasherPoints = null;
        $clawDamage = null;

        foreach($damages as $damage) {
            if ($damage->playerId == $playerId) {
                $damageNumber += $damage->damage;
                if ($damageDealerId == 0) {
                    $damageDealerId = $damage->damageDealerId;
                }
                if ($cardType == 0) {
                    $cardType = $damage->cardType;
                }
                if ($smasherPoints === null) {
                    $smasherPoints = $damage->smasherPoints;
                }
                $giveShrinkRayToken += $damage->giveShrinkRayToken;
                $givePoisonSpitToken += $damage->givePoisonSpitToken;
                if ($clawDamage === null) {
                    $clawDamage = $damage->clawDamage;
                }
            }
        }
        return $damageNumber == 0 ? null : new Damage($playerId, $damageNumber, $damageDealerId, $cardType, $clawDamage);
    }

    function getDamageIntervention() {
        return $this->getGlobalVariable(CANCEL_DAMAGE_INTERVENTION.$this->getStackedStateSuffix());
    }

    function setDamageIntervention($intervention) {
        $this->setGlobalVariable(CANCEL_DAMAGE_INTERVENTION.$this->getStackedStateSuffix(), $intervention);
    }
}
