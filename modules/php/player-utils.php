<?php

namespace KOT\States;

require_once(__DIR__.'/objects/damage.php');

use KOT\Objects\Damage;

trait PlayerUtilTrait {

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
        $sql = "SELECT count(*) FROM `player` where `player_id` = $playerId AND `player_score` = (select min(`player_score`) from `player` where player_eliminated = 0 AND player_dead = 0) AND (SELECT count(*) FROM `player` where player_eliminated = 0 AND player_dead = 0 and `player_score` = (select min(`player_score`) from `player` where player_eliminated = 0 AND player_dead = 0)) = 1";
        return intval(self::getUniqueValueFromDB($sql)) > 0;
    }

    function changeMaxHealth(int $playerId) {
        $health = $this->getPlayerHealth($playerId);
        $maxHealth = $this->getPlayerMaxHealth($playerId);

        if ($health > $maxHealth) {
            $health = $maxHealth;
            self::DbQuery("UPDATE player SET `player_health` = $health where `player_id` = $playerId");
        }

        if (intval(self::getUniqueValueFromDB("SELECT leave_tokyo_under FROM player where `player_id` = $playerId")) > $maxHealth) {
            self::DbQuery("UPDATE player SET `leave_tokyo_under` = $maxHealth where `player_id` = $playerId");
            self::notifyPlayer($playerId, 'updateLeaveTokyoUnder', '', [
                'under' => $maxHealth,
            ]);
        }

        if (intval(self::getUniqueValueFromDB("SELECT stay_tokyo_over FROM player where `player_id` = $playerId")) > $maxHealth) {
            self::DbQuery("UPDATE player SET `stay_tokyo_over` = $maxHealth where `player_id` = $playerId");
            self::notifyPlayer($playerId, 'updateStayTokyoOver', '', [
                'over' => $maxHealth,
            ]);
        }

        self::notifyAllPlayers('maxHealth', '', [
            'playerId' => $playerId,
            'health' => $health,
            'maxHealth' => $maxHealth,
        ]);
    }

    function autoLeave(int $playerId, int $health) {
        $leaveUnder = intval(self::getUniqueValueFromDB("SELECT leave_tokyo_under FROM `player` where `player_id` = $playerId"));

        if ($leaveUnder == 0) {
            return false;
        }

        $leave = $health < $leaveUnder;

        if ($leave) {
            $this->leaveTokyo($playerId);
        
            // burrowing
            $countBurrowing = $this->countCardOfType($playerId, BURROWING_CARD);
            if ($countBurrowing > 0) {
                self::setGameStateValue('loseHeartEnteringTokyo', $countBurrowing);
            }
        }

        return $leave;
    }

    function autoStay(int $playerId, int $health) {
        $stayOver = intval(self::getUniqueValueFromDB("SELECT stay_tokyo_over FROM `player` where `player_id` = $playerId"));

        if ($stayOver == 0) {
            return false;
        }

        $healthAfterJets = $health;        

        $jetsDamages = $this->getGlobalVariable(JETS_DAMAGES);
        if ($jetsDamages != null) {
            foreach($jetsDamages as $jetsDamage) {
                if ($jetsDamage->playerId == $playerId) {
                    $healthAfterJets -= $jetsDamage->damage;
                }
            }
        }

        $stay = $healthAfterJets >= $stayOver - 1;

        if ($stay) {
            $this->notifStayInTokyo($playerId);
        }

        return $stay;
    }

    function setLeaveTokyoUnder(int $under) {
        $playerId = self::getCurrentPlayerId(); // current, not active !

        self::DbQuery("UPDATE player SET `leave_tokyo_under` = $under where `player_id` = $playerId");

        self::notifyPlayer($playerId, 'updateLeaveTokyoUnder', '', [
            'under' => $under,
        ]);

        $stayOver = intval(self::getUniqueValueFromDB("SELECT stay_tokyo_over FROM `player` where `player_id` = $playerId"));
        if ($stayOver != 0 && $stayOver < $under) {
            self::DbQuery("UPDATE player SET `stay_tokyo_over` = 0 where `player_id` = $playerId");
            self::notifyPlayer($playerId, 'updateStayTokyoOver', '', [
                'over' => 0,
            ]);
        }
    }

    function setStayTokyoOver(int $over) {
        $playerId = self::getCurrentPlayerId(); // current, not active !

        $leaveUnder = intval(self::getUniqueValueFromDB("SELECT leave_tokyo_under FROM `player` where `player_id` = $playerId"));
        if ($leaveUnder != 0 && $over <= $leaveUnder) {
            throw new \Error("Can't set StayInTokyoOver less than LeaveTokyoUnder");
        }

        self::DbQuery("UPDATE player SET `stay_tokyo_over` = $over where `player_id` = $playerId");

        self::notifyPlayer($playerId, 'updateStayTokyoOver', '', [
            'over' => $over,
        ]);
    }

    function asyncEliminatePlayer(int $playerId) {
        $scoreAux = intval(self::getGameStateValue(KILL_PLAYERS_SCORE_AUX));
        self::DbQuery("UPDATE player SET `player_health` = 0, `player_score` = 0, player_location = 0, `player_dead` = $scoreAux where `player_id` = $playerId");

        self::notifyAllPlayers('kotPlayerEliminated', '', [
            'who_quits' => $playerId,
        ]);
    }

    function killDeadPlayers() {
        $activePlayerId = intval(self::getActivePlayerId());

        $sql = "SELECT player_id, player_dead FROM player WHERE player_eliminated = 0 AND player_dead > 0 ORDER BY player_no";
        $dbResults = self::getCollectionFromDB($sql);

        $killActive = false;

        foreach($dbResults as $dbResult) {
            $playerId = intval($dbResult['player_id']);
            $playerDead = intval($dbResult['player_dead']);

            self::DbQuery("UPDATE player SET `player_health` = 0, `player_score` = 0, `player_score_aux` = $playerDead, player_location = 0 where `player_id` = $playerId");
            self::eliminatePlayer($playerId);

            if ($activePlayerId === $playerId) {
                $killActive = true;
            }
        }

        return $killActive;
    }
}
