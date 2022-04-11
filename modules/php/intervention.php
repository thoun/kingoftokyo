<?php

namespace KOT\States;

require_once(__DIR__.'/objects/damage.php');
require_once(__DIR__.'/objects/player-intervention.php');

use KOT\Objects\Damage;
use KOT\Objects\CancelDamageIntervention;

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

        $isCancelDamage = strpos($interventionName, CANCEL_DAMAGE_INTERVENTION) === 0;
            
        if ($keep && $isCancelDamage) {
            // we check if player still ca do intervention (in case player got mimic, and mimicked camouflage player dies before mimic player intervention)

            $playerId = $intervention->remainingPlayersId[0];

            $damageDealerId = 0;
            $damage = 0;
            foreach($intervention->damages as $d) {
                if ($d->playerId == $playerId) {
                    $damage = $d->damage;
                    $damageDealerId = $d->damageDealerId;
                    break;
                }
            } 

            $keep = CancelDamageIntervention::canDoIntervention($this, $playerId, $damage, $damageDealerId);

            // if player cannot cancel damage, we apply them
            if (!$keep) {           
                foreach($intervention->damages as $d) {
                    if ($d->playerId == $playerId) {
                        $this->applyDamage($d->playerId, $d->damage, $d->damageDealerId, $d->cardType, $this->getActivePlayerId(), $d->giveShrinkRayToken, $d->givePoisonSpitToken, $d->smasherPoints);
                    }
                } 
            }
        }
        
        if ($keep) { // current player continues / next (intervention player) / or leaving transition
            $this->gamestate->setPlayersMultiactive([$intervention->remainingPlayersId[0]], 'transitionError', true);
        } else { // leaving transition
            if ($isCancelDamage && $this->isPowerUpExpansion()) {
                $this->goToState(ST_MULTIPLAYER_AFTER_RESOLVE_DAMAGE);
                return;
            }

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

    function reduceInterventionDamages(int $playerId, $intervention, int $reduceBy /* -1 for remove all for player*/) {
        if ($reduceBy === -1) {
            $intervention->damages = array_values(array_filter($intervention->damages, fn($d) => $d->playerId != $playerId));
            if ($intervention->allDamages) { // TODOPU remove this if
                $intervention->allDamages = array_values(array_filter($intervention->allDamages, fn($d) => $d->playerId != $playerId));
            }
        } else {
            $intervention->damages = $this->reduceInterventionDamagesForArray($playerId, $intervention->damages, $reduceBy);
            if ($intervention->allDamages) { // TODOPU remove this if
                $intervention->allDamages = $this->reduceInterventionDamagesForArray($playerId, $intervention->allDamages, $reduceBy);
            }
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
            }
        }
        return $damageNumber == 0 ? null : new Damage($playerId, $damageNumber, $damageDealerId, $cardType, $giveShrinkRayToken, $givePoisonSpitToken, $smasherPoints);
    }

    function getDamageIntervention() {
        return $this->getGlobalVariable(CANCEL_DAMAGE_INTERVENTION.$this->getStackedStateSuffix());
    }

    function setDamageIntervention($intervention) {
        $this->setGlobalVariable(CANCEL_DAMAGE_INTERVENTION.$this->getStackedStateSuffix(), $intervention);
    }
}
