<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;
use KOT\Objects\Damage;

class ResolveEndTurn extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct($game,
            id: ST_RESOLVE_END_TURN,
            type: StateType::GAME,
        );
    }

    function onEnteringState(int $activePlayerId) { 
        if ($this->game->powerUpExpansion->isActive()) {
            $freezeRayEvolutions = $this->game->getEvolutionsOfType($activePlayerId, FREEZE_RAY_EVOLUTION);
            foreach ($freezeRayEvolutions as $freezeRayEvolution) {
                $this->game->giveBackFreezeRay($activePlayerId, $freezeRayEvolution);
            }
        }

        // apply end of turn effects (after Selling Cards)
        
        // rooting for the underdog
        $countRootingForTheUnderdog = $this->game->countCardOfType($activePlayerId, ROOTING_FOR_THE_UNDERDOG_CARD);
        if ($countRootingForTheUnderdog > 0 && $this->game->isFewestStars($activePlayerId)) {
            $this->game->applyGetPoints($activePlayerId, $countRootingForTheUnderdog, ROOTING_FOR_THE_UNDERDOG_CARD);
        }

        // energy hoarder
        $countEnergyHoarder = $this->game->countCardOfType($activePlayerId, ENERGY_HOARDER_CARD);
        if ($countEnergyHoarder > 0) {
            $playerEnergy = $this->game->getPlayerEnergy($activePlayerId);
            $points = (int)floor($playerEnergy / 6);
            if ($points > 0) {
                $this->game->applyGetPoints($activePlayerId, $points * $countEnergyHoarder, ENERGY_HOARDER_CARD);
            }
        }

        // herbivore
        $countHerbivore = $this->game->countCardOfType($activePlayerId, HERBIVORE_CARD);
        if ($countHerbivore > 0 && $this->game->isDamageDealtThisTurn($activePlayerId) == 0) {
            $this->game->applyGetPoints($activePlayerId, $countHerbivore, HERBIVORE_CARD);
        }

        // solar powered
        $countSolarPowered = $this->game->countCardOfType($activePlayerId, SOLAR_POWERED_CARD);
        if ($countSolarPowered > 0 && $this->game->getPlayerEnergy($activePlayerId) == 0) {
            $this->game->applyGetEnergy($activePlayerId, $countSolarPowered, SOLAR_POWERED_CARD);
        }

        // natural selection
        $this->game->updateKillPlayersScoreAux();  
        $countNaturalSelection = $this->game->countCardOfType($activePlayerId, NATURAL_SELECTION_CARD);
        if ($countNaturalSelection > 0) {
            $diceCounts = $this->game->getGlobalVariable(DICE_COUNTS, true);
            if ($diceCounts[3] > 0) {
                $naturalSelectionDamage = new Damage($activePlayerId, $this->game->getPlayerHealth($activePlayerId), $activePlayerId, NATURAL_SELECTION_CARD);
                $this->game->applyDamage($naturalSelectionDamage);
            }
        }

        // apply poison
        $this->game->updateKillPlayersScoreAux();   
        
        $countPoison = $this->game->getPlayerPoisonTokens($activePlayerId);
        $damages = [];
        if ($countPoison > 0) {
            $damages[] = new Damage($activePlayerId, $countPoison, 0, POISON_SPIT_CARD);
        }

        $this->game->goToState(ST_END_TURN, $damages);
    }
}
