<?php

namespace KOT\States;

require_once(__DIR__.'/../Objects/damage.php');

use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

use const Bga\Games\KingOfTokyo\FINAL_PUSH_WICKEDNESS_TILE;

trait PlayerStateTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stBeforeStartTurn() {
        $playerId = $this->getActivePlayerId();        

        $this->DbQuery("DELETE FROM `turn_damages`");
        $this->DbQuery("UPDATE `player` SET `player_turn_energy` = 0, `player_turn_health` = 0, `player_turn_gained_health` = 0, `player_turn_entered_tokyo` = 0");
        $this->setGameStateValue(EXTRA_ROLLS, 0);
        $this->setGameStateValue(PSYCHIC_PROBE_ROLLED_A_3, 0);
        $this->setGameStateValue(SKIP_BUY_PHASE, 0);
        $this->setGameStateValue(CLOWN_ACTIVATED, 0);
        $this->setGameStateValue(CHEERLEADER_SUPPORT, 0);
        $this->setGameStateValue(RAGING_FLOOD_EXTRA_DIE, 0);
        $this->setGameStateValue(RAGING_FLOOD_EXTRA_DIE_SELECTED, 0);
        $this->setGlobalVariable(MADE_IN_A_LAB, []);
        $this->resetUsedCards();
        $this->setGlobalVariable(USED_WINGS, []);
        $this->setGlobalVariable(UNSTABLE_DNA_PLAYERS, []);
        $this->setGlobalVariable(CARD_BEING_BOUGHT, null);
        $this->setGlobalVariable(STARTED_TURN_IN_TOKYO, $this->getPlayersIdsInTokyo());

        $isPowerUpExpansion = $this->powerUpExpansion->isActive();

        if ($isPowerUpExpansion) {
            $blizzardCards = $this->getEvolutionsOfType($playerId, BLIZZARD_EVOLUTION);
            if (count($blizzardCards) > 0) {
                $this->removeEvolutions($playerId, $blizzardCards);
            }

            $player = $this->getPlayer($playerId);
            if ($player->askPlayEvolution == 2) {
                $this->applyAskPlayEvolution($playerId, 0);
            }
        }

        if (!$isPowerUpExpansion || count($this->getPlayersIdsWhoCouldPlayEvolutions([$playerId], $this->EVOLUTION_TO_PLAY_BEFORE_START)) == 0) {
            $this->goToState($this->redirectAfterBeforeStartTurn());
        }
    }

    function stStartTurn() {
        $playerId = $this->getActivePlayerId();

        $idsInTokyo = $this->getPlayersIdsInTokyo();
        foreach($idsInTokyo as $id) {
            $this->incStat(1, 'turnsInTokyo', $id);
        }

        $this->incStat(1, 'turnsNumber');
        $this->incStat(1, 'turnsNumber', $playerId);

        $activePlayerInTokyo = $this->inTokyo($playerId);

        // apply monster effects

        // battery monster
        $batteryMonsterCards = $this->getCardsOfType($playerId, BATTERY_MONSTER_CARD);
        foreach($batteryMonsterCards as $batteryMonsterCard) {
            $this->applyBatteryMonster($playerId, $batteryMonsterCard);
        }

        // princess
        $princessCards = $this->getCardsOfType($playerId, PRINCESS_CARD);
        foreach($princessCards as $princessCard) {
            $this->applyGetPoints($playerId, 1, PRINCESS_CARD);
        }

        // nanobots
        if ($this->getPlayerHealth($playerId) < 3) {
            $countNanobots = $this->countCardOfType($playerId, NANOBOTS_CARD);
            if ($countNanobots > 0) {
                $this->applyGetHealth($playerId, 2 * $countNanobots, NANOBOTS_CARD, $playerId);
            }
        }

        // Sonic boomer
        if ($this->wickednessExpansion->isActive()) {
            $this->wickednessTiles->onStartTurn(new Context(
                $this, 
                currentPlayerId: $playerId,
                currentPlayerInTokyo: $activePlayerInTokyo
            ));
        }

        if ($this->kingKongExpansion->isActive()) {
            $this->kingKongExpansion->onPlayerStartTurn($playerId);
        }

        if ($this->cybertoothExpansion->isActive()) {
            $this->cybertoothExpansion->onPlayerStartTurn($playerId);
        }

        if ($this->powerUpExpansion->isActive()) {
            $coldWaveCards = $this->getEvolutionsOfType($playerId, COLD_WAVE_EVOLUTION);
            if (count($coldWaveCards) > 0) {
                $this->removeEvolutions($playerId, $coldWaveCards);
            }

            $mothershipEvolutionCards = $this->getEvolutionsOfType($playerId, MOTHERSHIP_SUPPORT_EVOLUTION);
            if (count($mothershipEvolutionCards) > 0) {
                $this->notifyPlayer($playerId, 'toggleMothershipSupportUsed', '', [
                    'playerId' => $playerId,
                    'used' => false,
                ]);
            }

            $encasedInIceCards = $this->getEvolutionsOfType($playerId, ENCASED_IN_ICE_EVOLUTION);
            if (count($encasedInIceCards) > 0 && intval($this->getGameStateValue(ENCASED_IN_ICE_DIE_ID)) > 0) {
                $this->setGameStateValue(ENCASED_IN_ICE_DIE_ID, 0);
            }
        }

        // apply in tokyo at start
        if ($activePlayerInTokyo) {
            // start turn in tokyo

            if ($this->isTwoPlayersVariant()) {
                $playerGettingEnergy = $this->getPlayerGettingEnergyOrHeart($playerId);

                if ($this->canGainEnergy($playerGettingEnergy)) {
                    $incEnergy = 1;

                    if ($playerId == $playerGettingEnergy) {
                        $this->notifyAllPlayers('log', clienttranslate('${player_name} starts turn in Tokyo and gains ${deltaEnergy} [Energy]'), [
                            'playerId' => $playerId,
                            'player_name' => $this->getPlayerNameById($playerId),
                            'deltaEnergy' => $incEnergy,
                        ]);
                    }
                    $this->applyGetEnergy($playerGettingEnergy, $incEnergy, 0);
                }
            } else {
                if ($this->canGainPoints($playerId) === null) {
                    $incScore = 2;
                    $this->applyGetPoints($playerId, $incScore, -1);

                    $this->notifyAllPlayers('points', clienttranslate('${player_name} starts turn in Tokyo and gains ${deltaPoints} [Star]'), [
                        'playerId' => $playerId,
                        'player_name' => $this->getPlayerNameById($playerId),
                        'points' => $this->getPlayerScore($playerId),
                        'deltaPoints' => $incScore,
                    ]);
                }
            }

            // urbavore
            $countUrbavore = $this->countCardOfType($playerId, URBAVORE_CARD);
            if ($countUrbavore > 0) {
                $this->applyGetPoints($playerId, $countUrbavore, URBAVORE_CARD);
            }

            if ($this->powerUpExpansion->isActive()) {
                $countIAmTheKing = $this->countEvolutionOfType($playerId, I_AM_THE_KING_EVOLUTION);
                if ($countIAmTheKing > 0) {
                    $this->applyGetPoints($playerId, $countIAmTheKing, 3000 + I_AM_THE_KING_EVOLUTION);
                }
                $countDefenderOfTokyo = $this->countEvolutionOfType($playerId, DEFENDER_OF_TOKYO_EVOLUTION);
                if ($countDefenderOfTokyo > 0) {
                    $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                    foreach ($otherPlayersIds as $otherPlayerId) {
                        $this->applyLosePoints($otherPlayerId, $countDefenderOfTokyo, 3000 + DEFENDER_OF_TOKYO_EVOLUTION);
                    }
                }                
            }
        }

        $damages = [];
        if ($this->anubisExpansion->isActive()) {
            $curseCardType = $this->anubisExpansion->getCurseCardType();
            $logCardType = 1000 + $curseCardType;
            switch($curseCardType) {
                case SET_S_STORM_CURSE_CARD:
                    $damages[] = new Damage($playerId, 1, 0, $logCardType);
                    break;
                case BUILDERS_UPRISING_CURSE_CARD: 
                    $this->applyLosePoints($playerId, 2, $logCardType);
                    break;
                case ORDEAL_OF_THE_MIGHTY_CURSE_CARD:
                    $playersIds = $this->getPlayersIdsWithMaxColumn('player_health');
                    foreach ($playersIds as $pId) {
                        $damages[] = new Damage($pId, 1, 0, $logCardType);
                    }
                    break;
                case ORDEAL_OF_THE_WEALTHY_CURSE_CARD:
                    $playersIds = $this->getPlayersIdsWithMaxColumn('player_score');
                    foreach ($playersIds as $pId) {
                        $this->applyLosePoints($pId, 1, $logCardType);
                    }
                    break;
                case ORDEAL_OF_THE_SPIRITUAL_CURSE_CARD:
                    $playersIds = $this->getPlayersIdsWithMaxColumn('player_energy');
                    foreach ($playersIds as $pId) {
                        $this->applyLoseEnergy($pId, 1, $logCardType);
                    }
                    break;
            }
        }

        // throw dice
        $this->startTurnInitDice();

        $redirectAfterStartTurn = $this->redirectAfterStartTurn($playerId);

        $this->goToState($redirectAfterStartTurn, $damages);
    }

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
