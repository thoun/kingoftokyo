<?php

namespace KOT\States;

require_once(__DIR__.'/../Objects/damage.php');

use KOT\Objects\Damage;

use const Bga\Games\KingOfTokyo\FINAL_PUSH_WICKEDNESS_TILE;

trait PlayerStateTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stInitialDiceRoll() {
        $playerId = $this->getActivePlayerId();

        if ($this->getPlayer($this->getActivePlayerId())->eliminated) {
            $this->goToState(ST_PLAYER_BUY_CARD);
            return;
        }

        $this->setGameStateValue(DICE_NUMBER, $this->getDiceNumber($playerId, true));
        $this->throwDice($playerId, true);

        if ($this->isMutantEvolutionVariant()) {
            $isBeastForm = $this->isBeastForm($playerId);
            $this->incStat(1, $isBeastForm ? 'turnsInBeastForm' : 'turnsInBipedForm', $playerId);
        }

        $this->goToState(ST_PLAYER_THROW_DICE);
    }

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

    function stLeaveTokyoApplyJets() {
        $jetsDamages = $this->getGlobalVariable(JETS_DAMAGES);
        
        $this->goToState(ST_ENTER_TOKYO_APPLY_BURROWING, $jetsDamages);
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
    
    function stEnterTokyoApplyBurrowing() {
        $playerId = $this->getActivePlayerId();

        $leaversWithUnstableDNA = $this->getLeaversWithUnstableDNA();  
        $nextState = count($leaversWithUnstableDNA) >= 1 && $leaversWithUnstableDNA[0] != $playerId ? ST_MULTIPLAYER_LEAVE_TOKYO_EXCHANGE_CARD : ST_MULTIPLAYER_BEFORE_ENTERING_TOKYO;

        // burrowing
        $leaversWithBurrowing = $this->getLeaversWithBurrowing();  
        $damages = [];  
        foreach($leaversWithBurrowing as $leaverWithBurrowingId) {
            $countBurrowing = $this->countCardOfType($leaverWithBurrowingId, BURROWING_CARD);
            if ($countBurrowing > 0) {
                $damages[] = new Damage($playerId, $countBurrowing, $leaverWithBurrowingId, BURROWING_CARD);
            }
        }

        // jagged tactician
        $leaversWithJaggedTactician = $this->getLeaversWithJaggedTactician();  
        foreach($leaversWithJaggedTactician as $leaverWithJaggedTacticianId) {
            $countJaggedTactician = $this->countCardOfType($leaverWithJaggedTacticianId, JAGGED_TACTICIAN_CARD);
            if ($countJaggedTactician > 0) {
                $damages[] = new Damage($playerId, $countJaggedTactician, $leaverWithJaggedTacticianId, JAGGED_TACTICIAN_CARD);
                $this->applyGetEnergy($leaverWithJaggedTacticianId, $countJaggedTactician, JAGGED_TACTICIAN_CARD);
            }
        }
        
        $this->setGlobalVariable(BURROWING_PLAYERS, []); 
        $this->setGlobalVariable(JAGGED_TACTICIAN_PLAYERS, []); 

        $this->goToState($nextState, $damages);
    }

        
    function stEnterTokyo() {
        $this->setGlobalVariable(SMASHED_PLAYERS_IN_TOKYO, []);

        $playerId = $this->getActivePlayerId();
        $damages = [];

        $preventEnterTokyo = boolval($this->getGameStateValue(PREVENT_ENTER_TOKYO));
        if (!$this->getPlayer($playerId)->eliminated && !$this->inTokyo($playerId) && !$preventEnterTokyo) { // enter only if burrowing doesn't kill player
            $this->moveToTokyoFreeSpot($playerId);

            if ($this->getPlayer($playerId)->turnEnteredTokyo) {
                // gamma blast
                $countGammaBlast = $this->countCardOfType($playerId, GAMMA_BLAST_CARD);
                if ($countGammaBlast > 0) {
                    $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                    foreach($otherPlayersIds as $pId) {
                        $damages[] = new Damage($pId, $countGammaBlast, $playerId, GAMMA_BLAST_CARD);
                    }
                }
            }
        }        
        if ($preventEnterTokyo) {
            $this->setGameStateValue(PREVENT_ENTER_TOKYO, 0);
        }

        $nextState = $this->powerUpExpansion->isActive() ? ST_PLAYER_AFTER_ENTERING_TOKYO : $this->redirectAfterEnterTokyo($playerId);

        $this->goToState($nextState, $damages);
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
