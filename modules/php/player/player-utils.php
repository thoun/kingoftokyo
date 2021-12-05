<?php

namespace KOT\States;

require_once(__DIR__.'/../objects/damage.php');

use KOT\Objects\Damage;

trait PlayerUtilTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////

    function isInvincible(int $playerId) {
        return in_array($playerId, $this->getGlobalVariable(USED_WINGS, true));
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

    function getLeaversWithBurrowing() {
        $leaversWithBurrowing = $this->getGlobalVariable(BURROWING_PLAYERS, true);
        return $leaversWithBurrowing !== null ? $leaversWithBurrowing : [];
    }

    function addLeaverWithBurrowing(int $playerId) {
        $countBurrowing = $this->countCardOfType($playerId, BURROWING_CARD);
        if ($countBurrowing > 0) {
            $playersIds = $this->getLeaversWithBurrowing();
            $playersIds[] = $playerId;
            $this->setGlobalVariable(BURROWING_PLAYERS, $playersIds);
        }
    }

    function autoLeave(int $playerId, int $health) {
        $leaveUnder = intval(self::getUniqueValueFromDB("SELECT leave_tokyo_under FROM `player` where `player_id` = $playerId"));

        if ($leaveUnder == 0) {
            return false;
        }

        $leave = $health < $leaveUnder;

        if ($leave) {
            $this->leaveTokyo($playerId);
            $this->addLeaverWithBurrowing($playerId);
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
            throw new \BgaUserException("Can't set StayInTokyoOver less than LeaveTokyoUnder");
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

    function isPlayerBerserk(int $playerId) {
        return boolval(self::getUniqueValueFromDB("SELECT player_berserk FROM `player` where `player_id` = $playerId"));
    }

    function setPlayerBerserk(int $playerId, bool $active) {
        self::DbQuery("UPDATE player SET `player_berserk` = ".intval($active)." where `player_id` = $playerId");

        // TODOCY add a message to notif
        self::notifyAllPlayers('setPlayerBerserk', '', [
            'playerId' => $playerId,
            'berserk' => $active,
        ]);
    }

    function getPlayerCultists(int $playerId) {
        return intval(self::getUniqueValueFromDB("SELECT player_cultists FROM `player` where `player_id` = $playerId"));
    }
    
    function applyGetCultist(int $playerId, int $dieValue) {
        self::DbQuery("UPDATE player SET `player_cultists` = `player_cultists` + 1 where `player_id` = $playerId");

        $diceStr = $this->getDieFaceLogName($dieValue, 0);

        $message = clienttranslate('${player_name} gains 1 cultist with 4 or more ${dice}');
        self::notifyAllPlayers('cultist', $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'cultists' => $this->getPlayerCultists($playerId),
            'isMaxHealth' => $this->getPlayerHealth($playerId) >= $this->getPlayerMaxHealth($playerId),
            'dice' => $diceStr,
        ]);

        self::incStat(1, 'gainedCultists', $playerId);
    }

    function applyLoseCultist(int $playerId, string $message) {
        self::DbQuery("UPDATE player SET `player_cultists` = `player_cultists` - 1 where `player_id` = $playerId");

        self::notifyAllPlayers('cultist', $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'cultists' => $this->getPlayerCultists($playerId),
            'isMaxHealth' => $this->getPlayerHealth($playerId) >= $this->getPlayerMaxHealth($playerId),
        ]);
    }

    function useRapidCultist(int $type) {
        $playerId = self::getCurrentPlayerId(); // current, not active !

        $this->applyUseRapidCultist($playerId, $type);
    }

    function applyUseRapidCultist(int $playerId, int $type) {

        if ($type != 4 && $type != 5) {
            throw new \BgaUserException('Wrong type for cultist');
        }

        if ($this->getPlayerCultists($playerId) == 0) {
            throw new \BgaUserException('No cultist');
        }

        if ($this->getPlayer($playerId)->eliminated) {
            throw new \BgaUserException('You can\'t heal when you\'re dead');
        }

        if ($type == 4 && $this->getPlayerHealth($playerId) >= $this->getPlayerMaxHealth($playerId)) {
            throw new \BgaUserException('You can\'t heal when you\'re already at full life');
        }

        if ($type == 4 && !$this->canGainHealth($playerId)) {
            throw new \BgaUserException(/* TODOAN self::_(*/'You cannot gain [Heart]'/*)*/);
        }

        if ($type == 5 && !$this->canGainEnergy($playerId)) {
            throw new \BgaUserException(/* TODOAN self::_(*/'You cannot gain [Energy]'/*)*/);
        }

        if ($type == 4) {
            $this->applyGetHealth($playerId, 1, 0, $playerId);
            $this->applyLoseCultist($playerId, clienttranslate('${player_name} use a Cultist to gain 1[Heart]'));
            self::incStat(1, 'cultistHeal', $playerId);
        } else if ($type == 5) {
            $this->applyGetEnergy($playerId, 1, 0);
            $this->applyLoseCultist($playerId, clienttranslate('${player_name} use a Cultist to gain 1[Energy]'));
            self::incStat(1, 'cultistEnergy', $playerId);
        }
    }

    function canGainHealth(int $playerId) {
        if ($this->isAnubisExpansion()) {
            $curseCardType = $this->getCurseCardType();

            if ($curseCardType == ISIS_S_DISGRACE_CURSE_CARD) {
                if ($playerId != $this->getPlayerIdWithGoldenScarab()) {
                    return false;
                }
            }
        }
        return true;
    }

    function canLoseHealth(int $playerId) {
        if ($this->isAnubisExpansion()) {
            $curseCardType = $this->getCurseCardType();

            if ($curseCardType == PHARAONIC_SKIN_CURSE_CARD) {
                if ($playerId == $this->getPlayerIdWithGoldenScarab()) {
                    return 1000 + PHARAONIC_SKIN_CURSE_CARD;
                }
            }
        }
        return null;
    }

    function canGainEnergy(int $playerId) {
        if ($this->isAnubisExpansion()) {
            $curseCardType = $this->getCurseCardType();

            if ($curseCardType == THOT_S_BLINDNESS_CURSE_CARD) {
                if ($playerId != $this->getPlayerIdWithGoldenScarab()) {
                    return false;
                }
            }
        }
        return true;
    }

    function canGainPoints(int $playerId) {
        if ($this->isAnubisExpansion()) {
            $curseCardType = $this->getCurseCardType();

            if ($curseCardType == TUTANKHAMUN_S_CURSE_CURSE_CARD) {
                if ($playerId != $this->getPlayerIdWithGoldenScarab()) {
                    return false;
                }
            }
        }
        return true;
    }

    function canEnterTokyo() {
        if ($this->isAnubisExpansion()) {
            $curseCardType = $this->getCurseCardType();

            if ($curseCardType == PHARAONIC_EGO_CURSE_CARD) {
                $dieOfFate = $this->getDieOfFate();
                if ($dieOfFate->value == 4) {
                    return false;
                }
            }

            if ($curseCardType == RESURRECTION_OF_OSIRIS_CURSE_CARD) {
                $dieOfFate = $this->getDieOfFate();
                if ($dieOfFate->value == 3) {
                    return false;
                }
            }
        }
        return true;
    }

    function canYieldTokyo() {
        if ($this->isAnubisExpansion()) {
            $curseCardType = $this->getCurseCardType();

            if ($curseCardType == PHARAONIC_EGO_CURSE_CARD) {
                return false;
            }
        }
        return true;
    }

    function canHealWithDice(int $playerId) {
        $inTokyo = $this->inTokyo($playerId);

        if ($this->isAnubisExpansion()) {
            $curseCardType = $this->getCurseCardType();

            if ($curseCardType == RESURRECTION_OF_OSIRIS_CURSE_CARD) {
                return $inTokyo;
            }
        }
        return !$inTokyo;
    }

    function canBuyPowerCard(int $playerId) {
        $canBuyCards = true;
        if ($this->isAnubisExpansion() && $this->getCurseCardType() == FORBIDDEN_LIBRARY_CURSE_CARD) {
            $canBuyCards = $playerId == $this->getPlayerIdWithGoldenScarab();
        }
        return $canBuyCards;
    }

    function replacePlayersInTokyo(int $playerId) {
        if (!$this->canEnterTokyo()) {
            return;
        }

        // remove other players in Tokyo
        $damages = [];
        $playerInTokyoCity = $this->getPlayerIdInTokyoCity();
        $playerInTokyoBay = $this->getPlayerIdInTokyoBay();
        if ($playerInTokyoBay != null && $playerInTokyoBay > 0 && $playerInTokyoBay != $playerId) {
            $this->leaveTokyo($playerInTokyoBay);

            // burrowing
            $countBurrowing = $this->countCardOfType($playerInTokyoBay, BURROWING_CARD);
            if ($countBurrowing > 0) {
                $damages[] = new Damage($playerId, $countBurrowing, $playerInTokyoBay, BURROWING_CARD);
            }
        }
        if ($playerInTokyoCity != null && $playerInTokyoCity > 0 && $playerInTokyoCity != $playerId) {
            $this->leaveTokyo($playerInTokyoCity);

            // burrowing
            $countBurrowing = $this->countCardOfType($playerInTokyoCity, BURROWING_CARD);
            if ($countBurrowing > 0) {
                $damages[] = new Damage($playerId, $countBurrowing, $playerInTokyoCity, BURROWING_CARD);
            }
        }

        if ($playerInTokyoBay == $playerId) {
            $this->moveFromTokyoBayToCity($playerId);
        } else if ($playerInTokyoCity != $playerId) {
            // take control of Tokyo
            $this->moveToTokyo($playerId, false);
        }
    
        return $damages;
    }

    function canUseSymbol(int $playerId, int $symbol) {

        if ($this->isAnubisExpansion()) {
            $curseCardType = $this->getCurseCardType();

            if ($symbol == 6 && $curseCardType == BURIED_IN_SAND_CURSE_CARD) {
                $dieOfFate = $this->getDieOfFate();
                return $dieOfFate->value != 3;
            }

            if ($symbol == 6 && $curseCardType == HOTEP_S_PEACE_CURSE_CARD) {
                return $playerId == $this->getPlayerIdWithGoldenScarab();
            }
        }
        return true;
    }

    function canUseFace(int $playerId, int $faceType) {

        if ($this->isAnubisExpansion()) {
            $curseCardType = $this->getCurseCardType();

            if ($curseCardType == BODY_SPIRIT_AND_KA_CURSE_CARD) {
                $dieOfFate = $this->getDieOfFate();
                if ($dieOfFate->value == 3) {
                    return !in_array($faceType, [41, 51, 61]);
                } else if ($dieOfFate->value == 4) {
                    return true;
                } else {
                    return in_array($faceType, [41, 51, 61]);
                }

            }
        }
        return true;
    }

    function canRerollSymbol(int $playerId, int $symbol) {

        if ($this->isAnubisExpansion()) {
            $curseCardType = $this->getCurseCardType();

            if ($symbol == 6 && $curseCardType == VENGEANCE_OF_HORUS_CURSE_CARD) {
                return false;
            }

            if ($symbol == 1 && $curseCardType == SCRIBE_S_PERSEVERANCE_CURSE_CARD) {
                return false;
            }
        }
        return true;
    }

    function redirectAfterStartTurn(int $playerId) {
        if ($this->canChangeMimickedCardWickednessTile()) {
            return ST_PLAYER_CHANGE_MIMICKED_CARD_WICKEDNESS_TILE;
        }
        return $this->redirectAfterChangeMimickWickednessTile($playerId);
    }

    function redirectAfterChangeMimickWickednessTile(int $playerId) {
        if ($this->canChangeMimickedCard()) {
            return ST_PLAYER_CHANGE_MIMICKED_CARD;
        }
        return $this->redirectAfterChangeMimick($playerId);
    }

    function redirectAfterChangeMimick(int $playerId) {
        if ($this->isAnubisExpansion() && $this->getCurseCardType() == KHEPRI_S_REBELLION_CURSE_CARD && $playerId != $this->getPlayerIdWithGoldenScarab()) {
            return ST_MULTIPLAYER_GIVE_SYMBOL_TO_ACTIVE_PLAYER;
        }
        return ST_INITIAL_DICE_ROLL;
    }
}
