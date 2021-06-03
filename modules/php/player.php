<?php

namespace KOT\States;

require_once(__DIR__.'/objects/damage.php');

use KOT\Objects\Damage;

trait PlayerTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////

    function isInvincible(int $playerId) {
        return array_search($playerId, $this->getGlobalVariable(USED_WINGS, true)) !== false;
    }

    function setInvincible(int $playerId) {        
        $usedWings = $this->getGlobalVariable(USED_WINGS, true);
        $usedWings[] = $playerId;
        $this->setGlobalVariable(USED_WINGS, $usedWings);
    }

    function isFewestStars(int $playerId) {
        $sql = "SELECT count(*) FROM `player` where `player_id` = $playerId AND `player_score` = (select min(`player_score`) from `player`) AND (SELECT count(*) FROM `player` where `player_score` = (select min(`player_score`) from `player`)) = 1";
        return intval(self::getUniqueValueFromDB($sql)) > 0;
    }

    function changeMaxHealth(int $playerId) {
        $health = $this->getPlayerHealth($playerId);
        $maxHealth = $this->getPlayerMaxHealth($playerId);

        if ($health > $maxHealth) {
            $health = $maxHealth;
            self::DbQuery("UPDATE player SET `player_health` = $health where `player_id` = $playerId");
        }

        self::notifyAllPlayers('maxHealth', '', [
            'playerId' => $playerId,
            'health' => $health,
            'maxHealth' => $maxHealth,
        ]);
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */

    function endTurn() {
        $this->checkAction('endTurn');
   
        $this->gamestate->nextState('endTurn');
    }

    function stayInTokyo() {
        $this->checkAction('stay');

        $playerId = self::getCurrentPlayerId();

        self::notifyAllPlayers("stayInTokyo", clienttranslate('${player_name} chooses to stay in Tokyo'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
        ]);
        
        // Make this player unactive now (and tell the machine state to use transtion "resume" if all players are now unactive
        $this->gamestate->setPlayerNonMultiactive($playerId, "resume");
    }

    function actionLeaveTokyo() {
        $this->checkAction('leave');

        $playerId = self::getCurrentPlayerId();

        $this->leaveTokyo($playerId);

        $jetsDamages = $this->getGlobalVariable(JETS_DAMAGES);
        $jetsDamages = array_filter($jetsDamages, function($damage) use ($playerId) { return $damage->playerId != $playerId; });
        $this->setGlobalVariable(JETS_DAMAGES, $jetsDamages);
        
        // burrowing
        $countBurrowing = $this->countCardOfType($playerId, BURROWING_CARD);
        if ($countBurrowing > 0) {
            self::setGameStateValue('loseHeartEnteringTokyo', $countBurrowing);
        }
    
        // Make this player unactive now (and tell the machine state to use transtion "resume" if all players are now unactive
        $this->gamestate->setPlayerNonMultiactive($playerId, "resume");
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stStartTurn() {
        $playerId = self::getActivePlayerId();

        self::incStat(1, 'turnsNumber', $playerId);

        $idsInTokyo = $this->getPlayersIdsInTokyo();
        foreach($idsInTokyo as $id) {
            self::incStat(1, 'turnsInTokyo', $id);
        }

        self::setGameStateValue('damageDoneByActivePlayer', 0);
        self::setGameStateValue(EXTRA_ROLLS, 0);
        $this->setGlobalVariable(MADE_IN_A_LAB, []);
        $this->resetUsedCards();
        $this->setGlobalVariable(USED_WINGS, []);

        // apply monster effects

        // battery monster
        $batteryMonsterCards = $this->getCardsOfType($playerId, BATTERY_MONSTER_CARD);
        foreach($batteryMonsterCards as $batteryMonsterCard) {
            $this->applyBatteryMonster($playerId, $batteryMonsterCard);
        }

        // apply in tokyo at start
        if ($this->inTokyo($playerId)) {
            // start turn in tokyo
            $incScore = 2;

            $this->applyGetPointsIgnoreCards($playerId, $incScore, -1);
            self::notifyAllPlayers('points', clienttranslate('${player_name} starts turn in Tokyo and wins ${deltaPoints} [Star]'), [
                'playerId' => $playerId,
                'player_name' => self::getActivePlayerName(),
                'points' => $this->getPlayerScore($playerId),
                'deltaPoints' => $incScore,
            ]);

            // urbavore
            $countUrbavore = $this->countCardOfType($playerId, URBAVORE_CARD);
            if ($countUrbavore > 0) {
                $this->applyGetPoints($playerId, $countUrbavore, URBAVORE_CARD);
            }
        }

        // throw dice

        self::setGameStateValue('throwNumber', 1);
        self::DbQuery("UPDATE dice SET `dice_value` = 0, `locked` = false, `rolled` = true");

        $this->throwDice($playerId);

        if ($this->canChangeMimickedCard()) {
            $this->gamestate->nextState('changeMimickedCard');
        } else {
            $this->gamestate->nextState('throw');
        }
    }

    function stLeaveTokyo() {
        $smashedPlayersInTokyo = $this->getGlobalVariable(SMASHED_PLAYERS_IN_TOKYO, true);
        if (count($smashedPlayersInTokyo) > 0) {
            $this->gamestate->setPlayersMultiactive($smashedPlayersInTokyo, 'resume');
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
        $loseHeartForBurrowing = intval(self::getGameStateValue('loseHeartEnteringTokyo'));
        if ($loseHeartForBurrowing > 0) {
            $damage = new Damage($playerId, $loseHeartForBurrowing, 0, BURROWING_CARD);
            $redirects = $this->resolveDamages([$damage], 'enterTokyoAfterBurrowing');

            self::setGameStateValue('loseHeartEnteringTokyo', 0);
        }        

        if (!$redirects) {
            $this->gamestate->nextState('next');
        }
    }

        
    function stEnterTokyo() {
        $this->setGlobalVariable(SMASHED_PLAYERS_IN_TOKYO, []);

        $playerId = self::getActivePlayerId();

        if ($this->getPlayerHealth($playerId) > 0 && !$this->inTokyo($playerId)) { // enter only if burrowing doesn't kill player
            if ($this->isTokyoEmpty(false)) {
                $this->moveToTokyo($playerId, false);
            } else if ($this->tokyoBayUsed() && $this->isTokyoEmpty(true)) {
                $this->moveToTokyo($playerId, true);
            }
        }

        if ($this->getMaxPlayerScore() >= MAX_POINT) {
            $this->gamestate->jumpToState(ST_END_GAME);
        } else {
            $this->gamestate->nextState('next');
        }
    }

    function stResolveEndTurn() {
        $playerId = self::getActivePlayerId();

        // apply end of turn effects (after Selling Cards)

        // energy hoarder
        $countEnergyHoarder = $this->countCardOfType($playerId, ENERGY_HOARDER_CARD);
        if ($countEnergyHoarder > 0) {
            $playerEnergy = $this->getPlayerEnergy($playerId);
            $points = floor($playerEnergy / 6);
            $this->applyGetPoints($playerId, $points * $countEnergyHoarder, ENERGY_HOARDER_CARD);
        }

        // herbivore
        $countHerbivore = $this->countCardOfType($playerId, HERBIVORE_CARD);
        if ($countHerbivore > 0 && intval(self::getGameStateValue('damageDoneByActivePlayer')) == 0) {
            $this->applyGetPoints($playerId, $countHerbivore, HERBIVORE_CARD);
        }

        // solar powered
        $countSolarPowered = $this->countCardOfType($playerId, SOLAR_POWERED_CARD);
        if ($countSolarPowered > 0 && $this->getPlayerEnergy($playerId) == 0) {
            $this->applyGetEnergy($playerId, $countSolarPowered, SOLAR_POWERED_CARD);
        }

        // apply poison
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
        //$playerId = self::getActivePlayerId();

        // poison may eliminate players
        /*$endGame = $this->eliminatePlayers($playerId);

        if ($endGame) {
            $this->gamestate->nextState('endGame');
        } else {*/
            $this->gamestate->nextState('nextPlayer');
        /*}*/
    }

    function stNextPlayer() {  
        $playersIds = $this->getPlayersIds();
        foreach($playersIds as $playerId) {
            // rooting for the underdog
            $countRootingForTheUnderdog = $this->countCardOfType($playerId, ROOTING_FOR_THE_UNDERDOG_CARD);
            if ($countRootingForTheUnderdog > 0 && $this->isFewestStars($playerId)) {
                $this->applyGetPoints($playerId, $countRootingForTheUnderdog, ROOTING_FOR_THE_UNDERDOG_CARD);
            }
        }
        
        $playerId = self::getActivePlayerId();

        $anotherTimeWithCard = 0;

        $freezeTimeMaxTurns = intval($this->getGameStateValue(FREEZE_TIME_MAX_TURNS));
        $freezeTimeCurrentTurn = intval($this->getGameStateValue(FREEZE_TIME_CURRENT_TURN));

        if ($freezeTimeMaxTurns > 0 && $freezeTimeCurrentTurn == $freezeTimeMaxTurns) {
            $this->setGameStateValue(FREEZE_TIME_CURRENT_TURN, 0);
            $this->setGameStateValue(FREEZE_TIME_MAX_TURNS, 0);
        } if ($freezeTimeCurrentTurn < $freezeTimeMaxTurns) { // extra turn for current player with one less die
            $anotherTimeWithCard = FREEZE_TIME_CARD;
            $this->incGameStateValue(FREEZE_TIME_CURRENT_TURN, 1);
        }

        if ($anotherTimeWithCard == 0 && intval($this->getGameStateValue(FRENZY_EXTRA_TURN)) == 1) { // extra turn for current player
            $anotherTimeWithCard = 109; // Frenzy
            $this->setGameStateValue(FRENZY_EXTRA_TURN, 0);            
        }
        
        if ($anotherTimeWithCard > 0) {
            self::notifyAllPlayers('playAgain', clienttranslate('${player_name} take another turn with ${card_name}'), [
                'playerId' => $playerId,
                'player_name' => self::getActivePlayerName(),
                'card_name' => $anotherTimeWithCard,
            ]);
        } else {
            $playerId = self::activeNextPlayer();
        }

        self::giveExtraTime($playerId);

        if ($this->getMaxPlayerScore() >= MAX_POINT) {
            $this->gamestate->jumpToState(ST_END_GAME);
        } else {
            $this->gamestate->nextState('nextPlayer');
        }
    }
}
