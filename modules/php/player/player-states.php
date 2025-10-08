<?php

namespace KOT\States;

require_once(__DIR__.'/../Objects/damage.php');

use const Bga\Games\KingOfTokyo\FINAL_PUSH_WICKEDNESS_TILE;

trait PlayerStateTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stLeaveTokyo() {
        if ($this->autoSkipImpossibleActions()) {
            $canYieldTokyo = $this->argLeaveTokyo()['canYieldTokyo'];
            $oneCanYield = $this->array_some($canYieldTokyo, fn($canYield) => $canYield);
            if (!$oneCanYield) {
                $this->goToState(ST_LEAVE_TOKYO_APPLY_JETS);
                return;
            }
        }

        $smashedPlayersInTokyo = $this->getGlobalVariable(SMASHED_PLAYERS_IN_TOKYO, true);
        $aliveSmashedPlayersInTokyo = [];

        foreach($smashedPlayersInTokyo as $smashedPlayerInTokyo) {
            if ($this->inTokyo($smashedPlayerInTokyo)) { // we check if player is still in Tokyo, it could have left with It has a child!
                if ($this->canYieldTokyo($smashedPlayerInTokyo)) {
                    $player = $this->getPlayer($smashedPlayerInTokyo);
                    if ($player->eliminated) {
                        $this->leaveTokyo($smashedPlayerInTokyo);
                    } else {
                        if (!$this->autoLeave($smashedPlayerInTokyo, $player->health) && !$this->autoStay($smashedPlayerInTokyo, $player->health)) {
                            $aliveSmashedPlayersInTokyo[] = $smashedPlayerInTokyo;
                        }
                    }
                }
            }
        }

        if (count($aliveSmashedPlayersInTokyo) > 0) {
            if ($this->powerUpExpansion->isActive()) {
                $playerId = $this->getActivePlayerId();
                $countChestThumping = $this->countEvolutionOfType($playerId, CHEST_THUMPING_EVOLUTION);
                if ($countChestThumping > 0 && $this->anubisExpansion->isActive() && $this->anubisExpansion->getCurseCardType() == PHARAONIC_EGO_CURSE_CARD) {
                    $countChestThumping = 0; // impossible to use Chest Thumping with Pharaonic Ego 
                }
                if ($countChestThumping > 0) {
                    $aliveSmashedPlayersInTokyo[] = $playerId;
                }
            }

            $this->gamestate->setPlayersMultiactive($aliveSmashedPlayersInTokyo, 'resume', true);
        } else {
            $this->goToState(ST_LEAVE_TOKYO_APPLY_JETS);
        }
    }

    function stBeforeEnteringTokyo() {
        if (!$this->powerUpExpansion->isActive() || !$this->tokyoHasFreeSpot()) {
            $this->goToState($this->redirectAfterHalfMovePhase());
            return;
        }

        $playerId = $this->getActivePlayerId();
        $otherPlayersIds = $this->getOtherPlayersIds($playerId);
        $couldPlay = array_values(array_filter($otherPlayersIds, fn($pId) => 
            $this->getPlayersIdsWhoCouldPlayEvolutions([$pId], $this->EVOLUTION_TO_PLAY_BEFORE_ENTERING_TOKYO))
        );
        
        if ($this->getMimickedEvolutionType() == FELINE_MOTOR_EVOLUTION) {
            $icyReflection = $this->getEvolutionCardsByType(ICY_REFLECTION_EVOLUTION)[0];
            if (in_array($icyReflection->location_arg, $otherPlayersIds)) {
                $couldPlay[] = $icyReflection->location_arg;
            }
        }

        if (count($couldPlay) > 0) {
            $this->gamestate->setPlayersMultiactive($couldPlay, 'next', true);
        } else {
            $this->goToState($this->redirectAfterHalfMovePhase());
        }
    }
    
    function stAfterEnteringTokyo() {
        $playerId = $this->getActivePlayerId();
        $player = $this->getPlayer($playerId); 

        $couldPlay = $this->getPlayersIdsWhoCouldPlayEvolutions(
            [$playerId], 
            $player->location == 0 || !$player->turnEnteredTokyo ? $this->EVOLUTION_TO_PLAY_AFTER_NOT_ENTERING_TOKYO : $this->EVOLUTION_TO_PLAY_AFTER_ENTERING_TOKYO
        );
        $couldPlay = [$playerId];

        if (count($couldPlay) == 0) {
            $this->goToState($this->redirectAfterEnterTokyo($playerId));
        }
    }

    private function activeNextAlivePlayer() {
        if ($this->getRemainingPlayers() < 1) { // to avoid infinite loop
            return $this->getActivePlayerId();
        }

        $playerId = $this->activeNextPlayer();
        while ($this->getPlayer($playerId)->eliminated) {
            $playerId = $this->activeNextPlayer();
        }

        return $playerId;
    }

    public function activateNextPlayer() {
        $frenzyExtraTurnForOpportunist = intval($this->getGameStateValue(FRENZY_EXTRA_TURN_FOR_OPPORTUNIST));
        $playerBeforeFrenzyExtraTurnForOpportunist = intval($this->getGameStateValue(PLAYER_BEFORE_FRENZY_EXTRA_TURN_FOR_OPPORTUNIST));
        if ($frenzyExtraTurnForOpportunist > 0 && !$this->getPlayer($frenzyExtraTurnForOpportunist)->eliminated) {
            $this->gamestate->changeActivePlayer($frenzyExtraTurnForOpportunist);
            $playerId = $frenzyExtraTurnForOpportunist;
            $this->setGameStateValue(FRENZY_EXTRA_TURN_FOR_OPPORTUNIST, 0);

            $this->notifyAllPlayers('playAgain', clienttranslate('${player_name} takes another turn with ${card_name}'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerNameById($playerId),
                'card_name' => FRENZY_CARD,
            ]);

        } else if ($playerBeforeFrenzyExtraTurnForOpportunist > 0) {
            $this->gamestate->changeActivePlayer($playerBeforeFrenzyExtraTurnForOpportunist);
            $playerId = $this->activeNextAlivePlayer();
            $this->setGameStateValue(PLAYER_BEFORE_FRENZY_EXTRA_TURN_FOR_OPPORTUNIST, 0);
        } else {
            $playerId = $this->activeNextAlivePlayer();
        }

        return $playerId;
    }
}
