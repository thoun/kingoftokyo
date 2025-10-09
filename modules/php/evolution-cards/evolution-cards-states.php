<?php

namespace KOT\States;

require_once(__DIR__.'/../Objects/question.php');

use KOT\Objects\Question;

trait EvolutionCardsStateTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stAfterResolveDamage() {
        $intervention = $this->getDamageIntervention();

        $activePlayerId = $this->getActivePlayerId();
        $freezeRayEvolutions = $this->getEvolutionsOfType($activePlayerId, FREEZE_RAY_EVOLUTION);
        $freezeRayEvolution = $this->array_find($freezeRayEvolutions, fn($evolution) => $evolution->ownerId == $activePlayerId);
        if ($freezeRayEvolution != null) {
            $woundDamagesByFreezeRayOwner = array_values(array_filter($intervention->damages, fn($damage) => 
                $damage->clawDamage != null && $damage->damageDealerId == $activePlayerId && $damage->effectiveDamage > 0
            ));
            $woundedPlayersByFreezeRayOwner = array_values(array_unique(array_map(fn($damage) => $damage->playerId, $woundDamagesByFreezeRayOwner)));
            $woundedPlayersByFreezeRayOwner = array_values(array_filter($woundedPlayersByFreezeRayOwner, fn($playerId) => $this->inTokyo($playerId)));

            if (count($woundedPlayersByFreezeRayOwner) === 1) {
                $this->giveFreezeRay($activePlayerId, $woundedPlayersByFreezeRayOwner[0], $freezeRayEvolution);
            } else if (count($woundedPlayersByFreezeRayOwner) > 1) {
                $this->freezeRayChooseOpponentQuestion($activePlayerId, $woundedPlayersByFreezeRayOwner, $freezeRayEvolution);
                return;
            }
        }

        if (!$intervention->targetAcquiredAsked) {
            $askTargetAcquired = $this->askTargetAcquired($intervention->allDamages); // TODOPU when damage fully cancel, remove it from allDamages too

            $intervention->targetAcquiredAsked = true;
            $this->setDamageIntervention($intervention);

            if ($askTargetAcquired) {
                return;
            }
        }

        if (!$intervention->lightningArmorAsked) {
            $askLightningArmor = $this->askLightningArmor($intervention->allDamages); // TODOPU when damage fully cancel, remove it from allDamages too

            $intervention->lightningArmorAsked = true;
            $this->setDamageIntervention($intervention);

            if ($askLightningArmor) {
                return;
            }
        }

        $this->goToState($intervention->endState);
    }

}
