<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\Game;

class AfterResolveDamage extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_AFTER_RESOLVE_DAMAGE,
            type: StateType::GAME,
        );
    }

    public function onEnteringState(int $activePlayerId): void {
        $intervention = $this->game->getDamageIntervention();

        $freezeRayEvolutions = $this->game->getEvolutionsOfType($activePlayerId, \FREEZE_RAY_EVOLUTION);
        $freezeRayEvolution = Arrays::find($freezeRayEvolutions, fn($evolution) => $evolution->ownerId == $activePlayerId);
        if ($freezeRayEvolution !== null) {
            $woundDamagesByFreezeRayOwner = array_values(array_filter(
                $intervention->damages,
                fn($damage) => $damage->clawDamage != null && $damage->damageDealerId == $activePlayerId && $damage->effectiveDamage > 0,
            ));
            $woundedPlayersByFreezeRayOwner = array_values(array_unique(array_map(fn($damage) => $damage->playerId, $woundDamagesByFreezeRayOwner)));
            $woundedPlayersByFreezeRayOwner = array_values(array_filter($woundedPlayersByFreezeRayOwner, fn($playerId) => $this->game->inTokyo($playerId)));

            if (count($woundedPlayersByFreezeRayOwner) === 1) {
                $this->game->giveFreezeRay($activePlayerId, $woundedPlayersByFreezeRayOwner[0], $freezeRayEvolution);
            } elseif (count($woundedPlayersByFreezeRayOwner) > 1) {
                $this->game->freezeRayChooseOpponentQuestion($activePlayerId, $woundedPlayersByFreezeRayOwner, $freezeRayEvolution);
                return;
            }
        }

        if (!$intervention->targetAcquiredAsked) {
            $askTargetAcquired = $this->game->askTargetAcquired($intervention->allDamages);

            $intervention->targetAcquiredAsked = true;
            $this->game->setDamageIntervention($intervention);

            if ($askTargetAcquired) {
                return;
            }
        }

        if (!$intervention->lightningArmorAsked) {
            $askLightningArmor = $this->game->askLightningArmor($intervention->allDamages);

            $intervention->lightningArmorAsked = true;
            $this->game->setDamageIntervention($intervention);

            if ($askLightningArmor) {
                return;
            }
        }

        $this->game->goToState($intervention->endState);
    }
}

