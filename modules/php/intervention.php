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
        //$this->debug([$intervention->nextState, $intervention->remainingPlayersId]);
        if ($keep && $interventionName === CANCEL_DAMAGE_INTERVENTION) {
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
                        $this->applyDamage($d->playerId, $d->damage, $d->damageDealerId, $d->cardType, self::getActivePlayerId(), $d->giveShrinkRayToken, $d->givePoisonSpitToken);
                    }
                } 
            }
        }
        
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

    function reduceInterventionDamages(int $playerId, array $damages, int $reduceBy): array {
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

        foreach($damages as $damage) {
            if ($damage->playerId == $playerId) {
                $damageNumber += $damage->damage;
                if ($damageDealerId == 0) {
                    $damageDealerId = $damage->damageDealerId;
                }
                if ($cardType == 0) {
                    $cardType = $damage->cardType;
                }
                $giveShrinkRayToken += $damage->giveShrinkRayToken;
                $givePoisonSpitToken += $damage->givePoisonSpitToken;
            }
        }
        return $damageNumber == 0 ? null : new Damage($playerId, $damageNumber, $damageDealerId, $cardType, $giveShrinkRayToken, $givePoisonSpitToken);
    }
}
