<?php

namespace KOT\States;

require_once(__DIR__.'/../objects/damage.php');

use KOT\Objects\Damage;

trait PlayerStateTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stStartTurn() {
        $playerId = self::getActivePlayerId();

        self::incStat(1, 'turnsNumber');
        self::incStat(1, 'turnsNumber', $playerId);

        $idsInTokyo = $this->getPlayersIdsInTokyo();
        foreach($idsInTokyo as $id) {
            self::incStat(1, 'turnsInTokyo', $id);
        }

        self::DbQuery("DELETE FROM `turn_damages` WHERE 1");
        self::setGameStateValue(EXTRA_ROLLS, 0);
        self::setGameStateValue(PSYCHIC_PROBE_ROLLED_A_3, 0);
        self::setGameStateValue(SKIP_BUY_PHASE, 0);
        self::setGameStateValue(CLOWN_ACTIVATED, 0);
        self::setGameStateValue(CHEERLEADER_SUPPORT, 0);
        self::setGameStateValue(RAGING_FLOOD_EXTRA_DIE, 0);
        $this->setGlobalVariable(MADE_IN_A_LAB, []);
        $this->resetUsedCards();
        $this->setGlobalVariable(USED_WINGS, []);

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
        if ($this->isWickednessExpansion()) {
            if ($this->gotWickednessTile($playerId, TIRELESS_WICKEDNESS_TILE)) {
                $this->applyGetEnergy($playerId, 1, 2000 + TIRELESS_WICKEDNESS_TILE);
            }
            if ($this->gotWickednessTile($playerId, ETERNAL_WICKEDNESS_TILE)) {
                $this->applyGetHealth($playerId, 1, 2000 + ETERNAL_WICKEDNESS_TILE, $playerId);
            }
            if ($this->gotWickednessTile($playerId, SONIC_BOOMER_WICKEDNESS_TILE)) {
                $this->applyGetPoints($playerId, 1, 2000 + SONIC_BOOMER_WICKEDNESS_TILE);
            }
        }

        if ($this->isKingKongExpansion()) {
            $towerLevels = $this->getTokyoTowerLevels($playerId);

            foreach ($towerLevels as $level) {
                if ($level == 1 || $level == 2) {
                    if ($this->canGainHealth($playerId)) {
                        self::notifyAllPlayers('log', /*client TODOKK translate(*/'${player_name} starts turn with Tokyo Tower level ${level} and gains 1[Heart]'/*)*/, [
                            'playerId' => $playerId,
                            'player_name' => $this->getPlayerName($playerId),
                            'level' => $level,
                        ]);

                        $this->applyGetHealth($playerId, 1, 0, $playerId);
                    }
                }

                if ($level == 2) {
                    $playerGettingEnergy = $this->getPlayerGettingEnergyOrHeart($playerId);

                    if ($this->canGainEnergy($playerGettingEnergy)) {
        
                        if ($playerId == $playerGettingEnergy) {
                            self::notifyAllPlayers('log', /*client TODOKK translate(*/'${player_name} starts turn with Tokyo Tower level ${level} and gains 1[Energy]'/*)*/, [
                                'playerId' => $playerId,
                                'player_name' => $this->getPlayerName($playerId),
                                'level' => $level,
                            ]);
                        }

                        $this->applyGetEnergy($playerGettingEnergy, 1, 0);
                    }
                }
            }
        }

        // apply in tokyo at start
        if ($this->inTokyo($playerId)) {
            // start turn in tokyo

            if ($this->isTwoPlayersVariant()) {
                $playerGettingEnergy = $this->getPlayerGettingEnergyOrHeart($playerId);

                if ($this->canGainEnergy($playerGettingEnergy)) {
                    $incEnergy = 1;

                    if ($playerId == $playerGettingEnergy) {
                        self::notifyAllPlayers('log', clienttranslate('${player_name} starts turn in Tokyo and gains ${deltaEnergy} [Energy]'), [
                            'playerId' => $playerId,
                            'player_name' => $this->getPlayerName($playerId),
                            'deltaEnergy' => $incEnergy,
                        ]);
                    }
                    $this->applyGetEnergy($playerGettingEnergy, $incEnergy, 0);
                }
            } else {
                if ($this->canGainPoints($playerId)) {
                    $incScore = 2;
                    $this->applyGetPoints($playerId, $incScore, -1);

                    self::notifyAllPlayers('points', clienttranslate('${player_name} starts turn in Tokyo and gains ${deltaPoints} [Star]'), [
                        'playerId' => $playerId,
                        'player_name' => $this->getPlayerName($playerId),
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

            if ($this->isWickednessExpansion() && $this->gotWickednessTile($playerId, DEFENDER_OF_TOKYO_WICKEDNESS_TILE)) {
                $this->applyDefenderOfTokyo($playerId);
            }
        }

        $damages = [];
        if ($this->isAnubisExpansion()) {
            $curseCardType = $this->getCurseCardType();
            $logCardType = 1000 + $curseCardType;
            switch($curseCardType) {
                case SET_S_STORM_CURSE_CARD:
                    $damages[] = new Damage($playerId, 1, $playerId, $logCardType);
                    break;
                case BUILDERS_UPRISING_CURSE_CARD: 
                    $this->applyLosePoints($playerId, 2, $logCardType);
                    break;
                case ORDEAL_OF_THE_MIGHTY_CURSE_CARD:
                    $playersIds = $this->getPlayersIdsWithMaxColumn('player_health');
                    foreach ($playersIds as $pId) {
                        $damages[] = new Damage($pId, 1, $playerId, $logCardType);
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

        self::setGameStateValue('throwNumber', 1);
        self::DbQuery("UPDATE dice SET `dice_value` = 0, `locked` = false, `rolled` = true, `discarded` = false");

        $redirects = false;
        $redirectAfterStartTurn = $this->redirectAfterStartTurn($playerId);

        if ($damages != null && count($damages) > 0) {
            $this->updateKillPlayersScoreAux();
            
            $redirects = $this->resolveDamages($damages, $redirectAfterStartTurn);
        }

        if (!$redirects) {
            $this->eliminatePlayers($playerId);
            
            $this->jumpToState($redirectAfterStartTurn, $playerId);
        }
    }

    function stInitialDiceRoll() {
        $playerId = self::getActivePlayerId();

        self::setGameStateValue(DICE_NUMBER, $this->getDiceNumber($playerId, true));
        $this->throwDice($playerId, true);

        $this->gamestate->nextState('');
    }

    function stLeaveTokyo() {
        if ($this->autoSkipImpossibleActions() && !$this->argLeaveTokyo()['canYieldTokyo']) {
            $this->gamestate->nextState('resume');
            return;
        }

        $smashedPlayersInTokyo = $this->getGlobalVariable(SMASHED_PLAYERS_IN_TOKYO, true);
        $aliveSmashedPlayersInTokyo = [];

        foreach($smashedPlayersInTokyo as $smashedPlayerInTokyo) {
            if ($this->inTokyo($smashedPlayerInTokyo)) { // we check if player is still in Tokyo, it could have left with It has a child!
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

        if (count($aliveSmashedPlayersInTokyo) > 0) {
            $this->gamestate->setPlayersMultiactive($aliveSmashedPlayersInTokyo, 'resume', true);
        } else {
            $this->gamestate->nextState('resume');
        }
    }

    function stLeaveTokyoApplyJets() {
        $jetsDamages = $this->getGlobalVariable(JETS_DAMAGES);
        
        $redirects = false;
        if (count($jetsDamages) > 0) {
            $redirects = $this->resolveDamages($jetsDamages, ST_ENTER_TOKYO_APPLY_BURROWING);
        }

        if (!$redirects) {
            $this->gamestate->nextState('next');
        }
    }
    
    function stEnterTokyoApplyBurrowing() {
        $playerId = self::getActivePlayerId();

        $redirects = false;
        // burrowing
        $leaversWithBurrowing = $this->getLeaversWithBurrowing();  
        $burrowingDamages = [];  
        foreach($leaversWithBurrowing as $leaverWithBurrowingId) {
            $countBurrowing = $this->countCardOfType($leaverWithBurrowingId, BURROWING_CARD);
            if ($countBurrowing > 0) {
                $burrowingDamages[] = new Damage($playerId, $countBurrowing, $leaverWithBurrowingId, BURROWING_CARD);
            }
        }
        if (count($burrowingDamages) > 0) {
            $redirects = $this->resolveDamages($burrowingDamages, 'enterTokyoAfterBurrowing');
        }
        
        $this->setGlobalVariable(BURROWING_PLAYERS, []); 
        

        if (!$redirects) {
            $this->gamestate->nextState('next');
        }
    }

        
    function stEnterTokyo() {
        $this->setGlobalVariable(SMASHED_PLAYERS_IN_TOKYO, []);

        $playerId = self::getActivePlayerId();

        if (!$this->getPlayer($playerId)->eliminated && !$this->inTokyo($playerId)) { // enter only if burrowing doesn't kill player
            if ($this->isTokyoEmpty(false)) {
                $this->moveToTokyo($playerId, false);
            } else if ($this->tokyoBayUsed() && $this->isTokyoEmpty(true)) {
                $this->moveToTokyo($playerId, true);
            }
        }

        if ($this->isHalloweenExpansion()) { 
            $this->gamestate->nextState('stealCostumeCard');
        } else if ($this->isMutantEvolutionVariant()) { 
            $this->gamestate->nextState('changeForm');
        } else {
            $this->gamestate->nextState('buyCard');
        }
    }

    function stResolveEndTurn() {
        $playerId = self::getActivePlayerId();

        // apply end of turn effects (after Selling Cards)
        
        // rooting for the underdog
        $countRootingForTheUnderdog = $this->countCardOfType($playerId, ROOTING_FOR_THE_UNDERDOG_CARD);
        if ($countRootingForTheUnderdog > 0 && $this->isFewestStars($playerId)) {
            $this->applyGetPoints($playerId, $countRootingForTheUnderdog, ROOTING_FOR_THE_UNDERDOG_CARD);
        }

        // energy hoarder
        $countEnergyHoarder = $this->countCardOfType($playerId, ENERGY_HOARDER_CARD);
        if ($countEnergyHoarder > 0) {
            $playerEnergy = $this->getPlayerEnergy($playerId);
            $points = floor($playerEnergy / 6);
            if ($points > 0) {
                $this->applyGetPoints($playerId, $points * $countEnergyHoarder, ENERGY_HOARDER_CARD);
            }
        }

        // herbivore
        $countHerbivore = $this->countCardOfType($playerId, HERBIVORE_CARD);
        if ($countHerbivore > 0 && $this->isDamageDealtThisTurn($playerId) == 0) {
            $this->applyGetPoints($playerId, $countHerbivore, HERBIVORE_CARD);
        }

        // solar powered
        $countSolarPowered = $this->countCardOfType($playerId, SOLAR_POWERED_CARD);
        if ($countSolarPowered > 0 && $this->getPlayerEnergy($playerId) == 0) {
            $this->applyGetEnergy($playerId, $countSolarPowered, SOLAR_POWERED_CARD);
        }

        // apply poison
        $this->updateKillPlayersScoreAux();   
        $redirects = false;
        $countPoison = $this->getPlayerPoisonTokens($playerId);
        if ($countPoison > 0) {
            $damage = new Damage($playerId, $countPoison, 0, POISON_SPIT_CARD);
            $redirects = $this->resolveDamages([$damage], 'endTurn');
        }

        if (!$redirects) {
            $this->gamestate->nextState('endTurn');
        }
    }

    function stEndTurn() {
        $this->gamestate->nextState('nextPlayer');
    }

    private function activateNextPlayer() {
        $frenzyExtraTurnForOpportunist = intval($this->getGameStateValue(FRENZY_EXTRA_TURN_FOR_OPPORTUNIST));
        $playerBeforeFrenzyExtraTurnForOpportunist = intval($this->getGameStateValue(PLAYER_BEFORE_FRENZY_EXTRA_TURN_FOR_OPPORTUNIST));
        if ($frenzyExtraTurnForOpportunist > 0 && !$this->getPlayer($frenzyExtraTurnForOpportunist)->eliminated) {
            $this->gamestate->changeActivePlayer($frenzyExtraTurnForOpportunist);
            $playerId = $frenzyExtraTurnForOpportunist;
            $this->setGameStateValue(FRENZY_EXTRA_TURN_FOR_OPPORTUNIST, 0);

            self::notifyAllPlayers('playAgain', clienttranslate('${player_name} takes another turn with ${card_name}'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'card_name' => FRENZY_CARD,
            ]);

        } else if ($playerBeforeFrenzyExtraTurnForOpportunist > 0) {
            $this->gamestate->changeActivePlayer($playerBeforeFrenzyExtraTurnForOpportunist);
            $playerId = self::activeNextPlayer();
            $this->setGameStateValue(PLAYER_BEFORE_FRENZY_EXTRA_TURN_FOR_OPPORTUNIST, 0);
        } else {
            $playerId = self::activeNextPlayer();
        }

        return $playerId;
    }

    function stNextPlayer() {
        $playerId = self::getActivePlayerId();

        $this->removeDiscardCards($playerId);

        $this->applyEndOfEachMonsterCards();

        // end of the extra turn with Builders' uprising (without die of fate)
        if (intval($this->getGameStateValue(BUILDERS_UPRISING_EXTRA_TURN)) == 2) {
            $this->setGameStateValue(BUILDERS_UPRISING_EXTRA_TURN, 0);
        } 

        $killPlayer = $this->killDeadPlayers();

        if ($killPlayer) {
            $this->setGameStateValue(FREEZE_TIME_CURRENT_TURN, 0);
            $this->setGameStateValue(FREEZE_TIME_MAX_TURNS, 0);
            $this->setGameStateValue(FRENZY_EXTRA_TURN, 0);
            $this->setGameStateValue(FINAL_PUSH_EXTRA_TURN, 0);
            $this->setGameStateValue(BUILDERS_UPRISING_EXTRA_TURN, 0);

            $playerId = $this->activateNextPlayer();
        } else {
            $anotherTimeWithCard = 0;

            $freezeTimeMaxTurns = intval($this->getGameStateValue(FREEZE_TIME_MAX_TURNS));
            $freezeTimeCurrentTurn = intval($this->getGameStateValue(FREEZE_TIME_CURRENT_TURN));

            if ($anotherTimeWithCard == 0 && intval($this->getGameStateValue(BUILDERS_UPRISING_EXTRA_TURN)) == 1) { // extra turn for current player
                $anotherTimeWithCard = 1000 + BUILDERS_UPRISING_CURSE_CARD; // Builders' uprising
                $this->setGameStateValue(BUILDERS_UPRISING_EXTRA_TURN, 2); 
            }

            if ($anotherTimeWithCard == 0 && $freezeTimeMaxTurns > 0 && $freezeTimeCurrentTurn == $freezeTimeMaxTurns) {
                $this->setGameStateValue(FREEZE_TIME_CURRENT_TURN, 0);
                $this->setGameStateValue(FREEZE_TIME_MAX_TURNS, 0);
            } if ($freezeTimeCurrentTurn < $freezeTimeMaxTurns) { // extra turn for current player with one less die
                $anotherTimeWithCard = FREEZE_TIME_CARD;
                $this->incGameStateValue(FREEZE_TIME_CURRENT_TURN, 1);
            }

            if ($anotherTimeWithCard == 0 && intval($this->getGameStateValue(FINAL_PUSH_EXTRA_TURN)) == 1) { // extra turn for current player
                $anotherTimeWithCard = 2000 + FINAL_PUSH_WICKEDNESS_TILE; // Final push
                $this->setGameStateValue(FINAL_PUSH_EXTRA_TURN, 0); 
                $this->removeWickednessTile($playerId, $this->getWickednessTileByType($playerId, FINAL_PUSH_WICKEDNESS_TILE));
            }

            if ($anotherTimeWithCard == 0 && intval($this->getGameStateValue(FRENZY_EXTRA_TURN)) == 1) { // extra turn for current player
                $anotherTimeWithCard = FRENZY_CARD; // Frenzy
                $this->setGameStateValue(FRENZY_EXTRA_TURN, 0);            
            }
            
            if ($anotherTimeWithCard > 0) {
                self::notifyAllPlayers('playAgain', clienttranslate('${player_name} takes another turn with ${card_name}'), [
                    'playerId' => $playerId,
                    'player_name' => $this->getPlayerName($playerId),
                    'card_name' => $anotherTimeWithCard,
                ]);
            } else {
                $playerId = $this->activateNextPlayer();
            }
        }

        if ($this->getRemainingPlayers() <= 1 || $this->getMaxPlayerScore() >= MAX_POINT) {
            $this->jumpToState(ST_END_GAME);
        } else {
            self::giveExtraTime($playerId);

            $this->gamestate->nextState('nextPlayer');
        }
    }
}
