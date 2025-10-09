<?php

namespace KOT\States;

require_once(__DIR__.'/../Objects/damage.php');

use Bga\GameFramework\Actions\CheckAction;
use KOT\Objects\Damage;

trait PlayerUtilTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////

    function setInvincible(int $playerId, string $varName) {        
        $usedWings = $this->getGlobalVariable($varName, true);
        $usedWings[] = $playerId;
        $this->setGlobalVariable($varName, $usedWings);
    }

    function isFewestStars(int $playerId) {
        $sql = "SELECT count(*) FROM `player` where `player_id` = $playerId AND `player_score` = (select min(`player_score`) from `player` where player_eliminated = 0 AND player_dead = 0) AND (SELECT count(*) FROM `player` where player_eliminated = 0 AND player_dead = 0 and `player_score` = (select min(`player_score`) from `player` where player_eliminated = 0 AND player_dead = 0)) = 1";
        return intval($this->getUniqueValueFromDB($sql)) > 0;
    }

    function changeMaxHealth(int $playerId) {
        $health = $this->getPlayerHealth($playerId);
        $maxHealth = $this->getPlayerMaxHealth($playerId);

        if ($health > $maxHealth) {
            $health = $maxHealth;
            $this->DbQuery("UPDATE player SET `player_health` = $health where `player_id` = $playerId");
        }

        if (intval($this->getUniqueValueFromDB("SELECT leave_tokyo_under FROM player where `player_id` = $playerId")) > $maxHealth) {
            $this->DbQuery("UPDATE player SET `leave_tokyo_under` = $maxHealth where `player_id` = $playerId");
            $this->notifyPlayer($playerId, 'updateLeaveTokyoUnder', '', [
                'under' => $maxHealth,
            ]);
        }

        if (intval($this->getUniqueValueFromDB("SELECT stay_tokyo_over FROM player where `player_id` = $playerId")) > $maxHealth) {
            $this->DbQuery("UPDATE player SET `stay_tokyo_over` = $maxHealth where `player_id` = $playerId");
            $this->notifyPlayer($playerId, 'updateStayTokyoOver', '', [
                'over' => $maxHealth,
            ]);
        }

        $this->notifyAllPlayers('maxHealth', '', [
            'playerId' => $playerId,
            'health' => $health,
            'maxHealth' => $maxHealth,
        ]);
    }

    function getLeaversWithBurrowing() {
        $leaversWithBurrowing = $this->getGlobalVariable(BURROWING_PLAYERS, true);
        return $leaversWithBurrowing !== null ? $leaversWithBurrowing : [];
    }

    function getLeaversWithUnstableDNA() {
        $leaversWithUnstableDNA = $this->getGlobalVariable(UNSTABLE_DNA_PLAYERS, true);
        return $leaversWithUnstableDNA !== null ? $leaversWithUnstableDNA : [];
    }

    function getLeaversWithJaggedTactician() {
        $leaversWithJaggedTactician = $this->getGlobalVariable(JAGGED_TACTICIAN_PLAYERS, true);
        return $leaversWithJaggedTactician !== null ? $leaversWithJaggedTactician : [];
    }

    function addLeaverWithBurrowingOrUnstableDNA(int $playerId) {
        $countBurrowing = $this->countCardOfType($playerId, BURROWING_CARD);
        if ($countBurrowing > 0) {
            $playersIds = $this->getLeaversWithBurrowing();
            $playersIds[] = $playerId;
            $this->setGlobalVariable(BURROWING_PLAYERS, $playersIds);
        }

        $countUnstableDNA = $this->countCardOfType($playerId, UNSTABLE_DNA_CARD, false); // TODODE check if unstable DNA can be mimicked somehow. If yes, remove false here, and create an intervention.
        if ($countUnstableDNA > 0) {
            $playersIds = $this->getLeaversWithBurrowing();
            $playersIds[] = $playerId;
            $this->setGlobalVariable(UNSTABLE_DNA_PLAYERS, $playersIds);
        }

        $countJaggedTactician = $this->countCardOfType($playerId, JAGGED_TACTICIAN_CARD);
        if ($countJaggedTactician > 0) {
            $playersIds = $this->getLeaversWithJaggedTactician();
            $playersIds[] = $playerId;
            $this->setGlobalVariable(JAGGED_TACTICIAN_PLAYERS, $playersIds);
        }
    }

    function autoLeave(int $playerId, int $health) {
        $leaveUnder = intval($this->getUniqueValueFromDB("SELECT leave_tokyo_under FROM `player` where `player_id` = $playerId"));

        if ($leaveUnder <= 1) {
            return false;
        }

        $leave = $health < $leaveUnder;

        if ($leave) {
            $this->yieldTokyo($playerId);
        }

        return $leave;
    }

    function autoStay(int $playerId, int $health) {
        $stayOver = intval($this->getUniqueValueFromDB("SELECT stay_tokyo_over FROM `player` where `player_id` = $playerId"));

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

    #[CheckAction(false)]
    function setLeaveTokyoUnder(int $under) {
        $playerId = $this->getCurrentPlayerId(); // current, not active !

        $this->DbQuery("UPDATE player SET `leave_tokyo_under` = $under where `player_id` = $playerId");

        $this->notifyPlayer($playerId, 'updateLeaveTokyoUnder', '', [
            'under' => $under,
        ]);

        $stayOver = intval($this->getUniqueValueFromDB("SELECT stay_tokyo_over FROM `player` where `player_id` = $playerId"));
        if ($stayOver != 0 && $stayOver < $under) {
            $this->DbQuery("UPDATE player SET `stay_tokyo_over` = 0 where `player_id` = $playerId");
            $this->notifyPlayer($playerId, 'updateStayTokyoOver', '', [
                'over' => 0,
            ]);
        }
    }

    #[CheckAction(false)]
    function setStayTokyoOver(int $over) {
        $playerId = $this->getCurrentPlayerId(); // current, not active !

        $leaveUnder = intval($this->getUniqueValueFromDB("SELECT leave_tokyo_under FROM `player` where `player_id` = $playerId"));
        if ($leaveUnder != 0 && $over != 0 && $over <= $leaveUnder) {
            throw new \BgaUserException("Can't set StayInTokyoOver less than LeaveTokyoUnder");
        }

        $this->DbQuery("UPDATE player SET `stay_tokyo_over` = $over where `player_id` = $playerId");

        $this->notifyPlayer($playerId, 'updateStayTokyoOver', '', [
            'over' => $over,
        ]);
    }

    function applyAskPlayEvolution(int $playerId, int $value) {
        $this->DbQuery("UPDATE player SET `ask_play_evolution` = $value where `player_id` = $playerId");

        $this->notifyPlayer($playerId, 'updateAskPlayEvolution', '', [
            'value' => $value,
        ]);
    }

    #[CheckAction(false)]
    function actSetAskPlayEvolution(int $value) {
        $playerId = $this->getCurrentPlayerId(); // current, not active !

        $this->applyAskPlayEvolution($playerId,  $value);
    }

    function asyncEliminatePlayer(int $playerId) {
        $scoreAux = intval($this->getGameStateValue(KILL_PLAYERS_SCORE_AUX));
        $this->DbQuery("UPDATE player SET `player_health` = 0, `player_score` = 0, player_location = 0, `player_dead` = $scoreAux where `player_id` = $playerId");

        $this->notifyAllPlayers('kotPlayerEliminated', '', [
            'who_quits' => $playerId,
        ]);
    }

    function killDeadPlayers() {
        $activePlayerId = intval($this->getActivePlayerId());

        $sql = "SELECT player_id, player_dead FROM player WHERE player_eliminated = 0 AND player_dead > 0 ORDER BY player_no";
        $dbResults = $this->getCollectionFromDb($sql);

        $killActive = false;

        foreach($dbResults as $dbResult) {
            $playerId = intval($dbResult['player_id']);
            $playerDead = intval($dbResult['player_dead']);

            $this->DbQuery("UPDATE player SET `player_health` = 0, `player_score` = 0, `player_score_aux` = $playerDead, player_location = 0 where `player_id` = $playerId");
            $this->safeEliminatePlayer($playerId);

            if ($activePlayerId === $playerId) {
                $killActive = true;
            }
        }

        return $killActive;
    }

    #[CheckAction(false)]
    function actUseRapidCultist(int $type) {
        $playerId = $this->getCurrentPlayerId(); // current, not active !

        $this->cthulhuExpansion->applyUseRapidCultist($playerId, $type);

        $this->updateCancelDamageIfNeeded($playerId);
    }

    function canGainHealth(int $playerId): bool {
        if ($this->anubisExpansion->isActive()) {
            return $this->anubisExpansion->canGainHealth($playerId);
        }
        return true;
    }

    function canLoseHealth(int $playerId, int $damage) {
        if ($this->anubisExpansion->isActive()) {
            $result = $this->anubisExpansion->canLoseHealth($playerId);
            if ($result !== null) {
                return $result;
            }
        }

        if (in_array($playerId, $this->getGlobalVariable(USED_WINGS, true))) {
            return WINGS_CARD;
        }

        if ($this->powerUpExpansion->isActive()) {
            if ($this->countEvolutionOfType($playerId, DETACHABLE_TAIL_EVOLUTION) > 0) {
                return 3000 + DETACHABLE_TAIL_EVOLUTION;
            }
            if ($this->countEvolutionOfType($playerId, RABBIT_S_FOOT_EVOLUTION) > 0) {
                return 3000 + RABBIT_S_FOOT_EVOLUTION;
            }
            if ($this->countEvolutionOfType($playerId, SIMIAN_SCAMPER_EVOLUTION) > 0) {
                return 3000 + SIMIAN_SCAMPER_EVOLUTION;
            }
        }

        if ($this->countCardOfType($playerId, HIBERNATION_CARD) > 0) {
            return HIBERNATION_CARD;
        }

        if ($damage == 1) {
            if ($this->countCardOfType($playerId, ARMOR_PLATING_CARD) > 0) {
                return ARMOR_PLATING_CARD;
            }
        }

        return null;
    }

    function canGainEnergy(int $playerId): bool {
        if ($this->anubisExpansion->isActive()) {
            return $this->anubisExpansion->canGainEnergy($playerId);
        }
        return true;
    }

    function canGainPoints(int $playerId): ?int {
        if ($this->anubisExpansion->isActive()) {
            $result = $this->anubisExpansion->canGainPoints($playerId);
            if ($result !== null) {
                return $result;
            }
        }
        return null;
    }

    function canEnterTokyo(int $playerId): bool {
        if ($this->anubisExpansion->isActive() && !$this->anubisExpansion->canEnterTokyo()) {
            return false;
        }

        if ($this->countCardOfType($playerId, HIBERNATION_CARD) > 0) {
            return false;
        }

        return true;
    }

    function canYieldTokyo(int $playerId) {
        if ($this->anubisExpansion->isActive() && !$this->anubisExpansion->canYieldTokyo()) {
            return false;
        }
        if ($this->powerUpExpansion->isActive()) {
            $blizzardOwner = $this->isEvolutionOnTable(BLIZZARD_EVOLUTION);
            if ($blizzardOwner != null && $blizzardOwner != $playerId) {
                return false;
            } 
        }
        return true;
    }

    function canHealWithDice(int $playerId): bool {
        $inTokyo = $this->inTokyo($playerId);

        if ($this->anubisExpansion->isActive()) {
            $result = $this->anubisExpansion->canHealWithDice($inTokyo);
            if ($result !== null) {
                return $result;
            }
        }

        if ($this->isZombified($playerId)) {
            return false;
        }

        return !$inTokyo;
    }

    function frozenFaces(int $playerId) {
        if (!$this->powerUpExpansion->isActive()) {
            return [];
        }

        $frozenFaces = [];
        $freezeRayCards = $this->getEvolutionsOfType($playerId, FREEZE_RAY_EVOLUTION);
        foreach ($freezeRayCards as $freezeRayCard) {
            if ($freezeRayCard->tokens > 0 && !in_array($freezeRayCard->tokens, $frozenFaces)) {
                $frozenFaces[] = $freezeRayCard->tokens;
            }
        }
        
        return $frozenFaces;
    }

    function canBuyPowerCard(int $playerId): bool {
        if ($this->anubisExpansion->isActive()) {
            return $this->anubisExpansion->canBuyPowerCard($playerId);
        }
        return true;
    }

    function replacePlayersInTokyo(int $playerId) {
        if (!$this->canEnterTokyo($playerId)) {
            return;
        }

        // remove other players in Tokyo
        $playerInTokyoCity = $this->getPlayerIdInTokyoCity();
        $playerInTokyoBay = $this->getPlayerIdInTokyoBay();
        if ($playerInTokyoBay != null && $playerInTokyoBay > 0 && $playerInTokyoBay != $playerId) {
            $this->leaveTokyo($playerInTokyoBay);
        }
        if ($playerInTokyoCity != null && $playerInTokyoCity > 0 && $playerInTokyoCity != $playerId) {
            $this->leaveTokyo($playerInTokyoCity);
        }

        if ($playerInTokyoBay == $playerId) {
            $this->moveFromTokyoBayToCity($playerId);
        } else if ($playerInTokyoCity != $playerId) {
            // take control of Tokyo
            $this->moveToTokyo($playerId, false);
        }
    }

    function canUseSymbol(int $playerId, int $symbol) {

        if ($this->anubisExpansion->isActive()) {
            $curseCardType = $this->anubisExpansion->getCurseCardType();

            if ($symbol == 6 && $curseCardType == BURIED_IN_SAND_CURSE_CARD) {
                $dieOfFate = $this->anubisExpansion->getDieOfFate();
                return $dieOfFate->value != 3;
            }

            if ($symbol == 6 && $curseCardType == HOTEP_S_PEACE_CURSE_CARD) {
                return $playerId == $this->anubisExpansion->getPlayerIdWithGoldenScarab();
            }
        }

        if ($symbol == 4 && $this->isZombified($playerId)) {
            return false;
        }

        return true;
    }

    function canUseFace(int $playerId, int $face) {

        if ($this->anubisExpansion->isActive()) {
            $curseCardType = $this->anubisExpansion->getCurseCardType();

            if ($curseCardType == BODY_SPIRIT_AND_KA_CURSE_CARD) {
                $dieOfFate = $this->anubisExpansion->getDieOfFate();
                if ($dieOfFate->value == 3) {
                    if (in_array($face, [4, 5, 6])) {
                        return false;
                    }
                } else if ($dieOfFate->value != 4) {
                    if (!in_array($face, [4, 5, 6])) {
                        return false;
                    }
                }

            }
        }

        if ($face == 4 && $this->isZombified($playerId)) {
            return false;
        }

        if ($this->powerUpExpansion->isActive()) {
            $freezeRayCards = $this->getEvolutionsOfType($playerId, FREEZE_RAY_EVOLUTION);
            foreach ($freezeRayCards as $freezeRayCard) {
                if ($freezeRayCard->tokens == $face) {
                    return false;
                }
            }
        }

        return true;
    }

    function canRerollSymbol(int $playerId, int $symbol, bool $isChangeActivePlayerDie = false) {

        if ($this->anubisExpansion->isActive()) {
            $curseCardType = $this->anubisExpansion->getCurseCardType();

            if ($symbol == 6 && $curseCardType == VENGEANCE_OF_HORUS_CURSE_CARD) {
                return false;
            }

            if ($symbol == 1 && $curseCardType == SCRIBE_S_PERSEVERANCE_CURSE_CARD && !$isChangeActivePlayerDie) {
                return false;
            }
        }
        return true;
    }

    function updateCancelDamageIfNeeded(int $rapidActionPlayerId) {
        if ($this->gamestate->state_id() == ST_MULTIPLAYER_CANCEL_DAMAGE) {
            $intervention = $this->getDamageIntervention();

            $playerId = $intervention && count($intervention->remainingPlayersId) > 0 ? $intervention->remainingPlayersId[0] : null;

            if ($playerId == $rapidActionPlayerId) {
                $args = $this->argCancelDamage($playerId, $intervention);                
                $this->notifyAllPlayers('updateCancelDamage', '', [
                    'cancelDamageArgs' => $args,
                ]);
                //$this->goToState(ST_MULTIPLAYER_CANCEL_DAMAGE); // to update args based on new health
            }
        }
    }
}
