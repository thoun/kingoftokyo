<?php

namespace KOT\States;

require_once(__DIR__.'/objects/dice.php');
require_once(__DIR__.'/objects/card.php');
require_once(__DIR__.'/objects/player.php');
require_once(__DIR__.'/objects/player-intervention.php');

use KOT\Objects\Card;
use KOT\Objects\Dice;
use KOT\Objects\Player;
use KOT\Objects\CancelDamageIntervention;

trait UtilTrait {

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////

    function array_find(array $array, callable $fn) {
        foreach ($array as $value) {
            if($fn($value)) {
                return $value;
            }
        }
        return null;
    }

    function array_some(array $array, callable $fn) {
        foreach ($array as $value) {
            if($fn($value)) {
                return true;
            }
        }
        return false;
    }

    function isTurnBased() {
        return intval($this->gamestate->table_globals[200]) >= 10;
    }

    function isTwoPlayersVariant() {
        return intval(self::getGameStateValue(TWO_PLAYERS_VARIANT_OPTION)) === 2 && $this->getPlayersNumber() == 2;
    }

    function isPowerUpExpansion() {
        return false;
    }

    function isHalloweenExpansion() {
        return intval(self::getGameStateValue(HALLOWEEN_EXPANSION_OPTION)) === 2;
    }

    function isKingKongExpansion() {
        return /*$this->getBgaEnvironment() == 'studio' ||*/ intval(self::getGameStateValue(KINGKONG_EXPANSION_OPTION)) === 2;
    }

    function isCybertoothExpansion() {
        return /*$this->getBgaEnvironment() == 'studio' ||*/ intval(self::getGameStateValue(CYBERTOOTH_EXPANSION_OPTION)) === 2;
    }

    function isMutantEvolutionVariant() {
        return /*$this->getBgaEnvironment() == 'studio' ||*/ intval(self::getGameStateValue(MUTANT_EVOLUTION_VARIANT_OPTION)) === 2;
    }

    function isCthulhuExpansion() {
        return /*$this->getBgaEnvironment() == 'studio' ||*/ intval(self::getGameStateValue(CTHULHU_EXPANSION_OPTION)) === 2;
    }

    function isAnubisExpansion() {
        return $this->getBgaEnvironment() == 'studio' || intval(self::getGameStateValue(ANUBIS_EXPANSION_OPTION)) === 2;
    }

    function isDarkEdition() {
        return false;
    }

    function autoSkipImpossibleActions() {
        return $this->isTurnBased() || intval(self::getGameStateValue(AUTO_SKIP_OPTION)) === 2;
    }

    function setGlobalVariable(string $name, /*object|array*/ $obj) {
        /*if ($obj == null) {
            throw new \Error('Global Variable null');
        }*/
        $jsonObj = json_encode($obj);
        self::DbQuery("INSERT INTO `global_variables`(`name`, `value`)  VALUES ('$name', '$jsonObj') ON DUPLICATE KEY UPDATE `value` = '$jsonObj'");
    }

    function getGlobalVariable(string $name, $asArray = null) {
        $json_obj = self::getUniqueValueFromDB("SELECT `value` FROM `global_variables` where `name` = '$name'");
        if ($json_obj) {
            $object = json_decode($json_obj, $asArray);
            return $object;
        } else {
            return null;
        }
    }

    function isUsedCard(int $cardId, $usedCards = null) {
        $cardsIds = $this->getUsedCard();
        return in_array($cardId, $cardsIds);
    }

    function setUsedCard(int $cardId) {
        $cardsIds = $this->getUsedCard();
        $cardsIds[] = $cardId;
        $this->setGlobalVariable(USED_CARDS, $cardsIds);
    }

    function getUsedCard() {
        return $this->getGlobalVariable(USED_CARDS, true);
    }

    function resetUsedCards() {
        $this->setGlobalVariable(USED_CARDS, []);
    }

    function deleteGlobalVariable(string $name) {
        self::DbQuery("DELETE FROM `global_variables` where `name` = '$name'");
    }

    function getMaxPlayerScore() {
        return intval(self::getUniqueValueFromDB("SELECT max(player_score) FROM player"));
    }

    function getPlayerName(int $playerId) {
        return self::getUniqueValueFromDb("SELECT player_name FROM player WHERE player_id = $playerId");
    }

    function getPlayerScore(int $playerId) {
        return intval(self::getUniqueValueFromDB("SELECT player_score FROM player where `player_id` = $playerId"));
    }

    function getPlayerHealth(int $playerId) {
        return intval(self::getUniqueValueFromDB("SELECT player_health FROM player where `player_id` = $playerId"));
    }

    function getPlayerEnergy(int $playerId) {
        return intval(self::getUniqueValueFromDB("SELECT player_energy FROM player where `player_id` = $playerId"));
    }

    function getPlayerPoisonTokens(int $playerId) {
        return intval(self::getUniqueValueFromDB("SELECT player_poison_tokens FROM player where `player_id` = $playerId"));
    }

    function getPlayerShrinkRayTokens(int $playerId) {
        return intval(self::getUniqueValueFromDB("SELECT player_shrink_ray_tokens FROM player where `player_id` = $playerId"));
    }

    function getRollNumber(int $playerId) {
        // giant brain
        $countGiantBrain = $this->countCardOfType($playerId, GIANT_BRAIN_CARD);
        // statue of libery
        $countStatueOfLiberty = $this->countCardOfType($playerId, STATUE_OF_LIBERTY_CARD);
        // energy drink
        $extraRolls = intval(self::getGameStateValue(EXTRA_ROLLS));
        $beastForm = ($this->isMutantEvolutionVariant() && $this->isBeastForm($playerId)) ? 1 : 0;

        $removedDieByBuriedInSand = false;
        $falseBlessing = 0;
        if ($this->isAnubisExpansion()) {
            $cardType = $this->getCurseCardType();

            if ($cardType == BURIED_IN_SAND_CURSE_CARD) {
                $dieOfFate = $this->getDieOfFate();

                if ($dieOfFate->value != 4) {
                    $removedDieByBuriedInSand = true;
                }
            }

            if ($cardType == FALSE_BLESSING_CURSE_CARD) {
                $falseBlessing = 1;
            }
        }

        $rollNumber = 3 + $countGiantBrain + $countStatueOfLiberty + $extraRolls + $beastForm + $falseBlessing;
        if ($rollNumber > 1 && $removedDieByBuriedInSand) {
            $rollNumber--;
        }
        return $rollNumber;
    }

    function getPlayerMaxHealth(int $playerId) {
        // even bigger
        $countEvenBigger = $this->countCardOfType($playerId, EVEN_BIGGER_CARD);
        return 10 + (2 * $countEvenBigger);
    }

    function getRemainingPlayers() {
        return intval(self::getUniqueValueFromDB( "SELECT count(*) FROM player WHERE player_eliminated = 0 AND player_dead = 0"));
    }

    function tokyoBayUsed() {
        return $this->getRemainingPlayers() > 4;
    }

    function isTokyoEmpty(bool $bay) {
        $location = $bay ? 2 : 1;
        $players = intval(self::getUniqueValueFromDB( "SELECT count(*) FROM player WHERE player_location = $location"));
        return $players == 0;
    }

    function moveToTokyo(int $playerId, bool $bay) {
        $location = $bay ? 2 : 1;
        $message = null;
        if ($this->isTwoPlayersVariant()) {
            $incEnergy = 1;
            self::DbQuery("UPDATE player SET player_location = $location where `player_id` = $playerId");
            $message = clienttranslate('${player_name} enters ${locationName} and gains 1 [Energy]');
            $this->applyGetEnergy($playerId, $incEnergy, -1);
        } else {
            $incScore = 1;
            self::DbQuery("UPDATE player SET player_location = $location where `player_id` = $playerId");
            $this->applyGetPoints($playerId, $incScore, -1);
            $message = clienttranslate('${player_name} enters ${locationName} and gains 1 [Star]');
        }

        $locationName = $bay ? _('Tokyo Bay') : _('Tokyo City');
        self::notifyAllPlayers("playerEntersTokyo", $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'location' => $location,
            'locationName' => $locationName,
            'points' => $this->getPlayerScore($playerId),
            'energy' => $this->getPlayerEnergy($playerId),
        ]);

        self::incStat(1, 'tokyoEnters', $playerId);
    }

    function leaveTokyo(int $playerId) {

        self::DbQuery("UPDATE player SET player_location = 0, `leave_tokyo_under` = null, `stay_tokyo_over` = null where `player_id` = $playerId");

        self::notifyAllPlayers("leaveTokyo", clienttranslate('${player_name} leaves Tokyo'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
        ]);
        self::notifyPlayer($playerId, 'updateLeaveTokyoUnder', '', [
            'under' => 0,
        ]);
        self::notifyPlayer($playerId, 'updateStayTokyoOver', '', [
            'over' => 0,
        ]);

        $jetsDamages = $this->getGlobalVariable(JETS_DAMAGES);
        if ($jetsDamages != null) {
            $jetsDamages = array_values(array_filter($jetsDamages, function($damage) use ($playerId) { return $damage->playerId != $playerId; }));
            $this->setGlobalVariable(JETS_DAMAGES, $jetsDamages);
        }

        self::incStat(1, 'tokyoLeaves', $playerId);
    }

    function moveFromTokyoBayToCity(int $playerId) {
        $location = 1;

        self::DbQuery("UPDATE player SET player_location =  $location where `player_id` = $playerId");

        $locationName = _('Tokyo City');
        self::notifyAllPlayers("playerEntersTokyo", clienttranslate('${player_name} enters ${locationName} !'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'location' => $location,
            'locationName' => $locationName,
            'points' => $this->getPlayerScore($playerId),
            'energy' => $this->getPlayerEnergy($playerId),
        ]);
    }

    function inTokyo(int $playerId) {
        $location = intval(self::getUniqueValueFromDB( "SELECT player_location FROM player WHERE player_id = $playerId"));
        return $location > 0;
    }

    // get players ids

    private function getPlayersIdsFromLocation(bool $inside) {
        $sign = $inside ? '>' : '=';
        $sql = "SELECT player_id FROM player WHERE player_location $sign 0 AND player_eliminated = 0 AND player_dead = 0 ORDER BY player_no";
        $dbResults = self::getCollectionFromDB($sql);
        return array_map(function($dbResult) { return intval($dbResult['player_id']); }, array_values($dbResults));
    }

    function getPlayerIdInTokyoCity() {
        $sql = "SELECT player_id FROM player WHERE player_location = 1 AND player_eliminated = 0 AND player_dead = 0 ORDER BY player_no";
        return intval(self::getUniqueValueFromDB($sql));
    }

    function getPlayerIdInTokyoBay() {
        $sql = "SELECT player_id FROM player WHERE player_location = 2 AND player_eliminated = 0 AND player_dead = 0 ORDER BY player_no";
        return intval(self::getUniqueValueFromDB($sql));
    }

    function getPlayersIdsInTokyo() {
        return $this->getPlayersIdsFromLocation(true);
    }

    /*function getPlayersIdsOutsideTokyo() {
        return $this->getPlayersIdsFromLocation(false);
    }*/

    function getPlayersIds() {
        $sql = "SELECT player_id FROM player WHERE player_eliminated = 0 AND player_dead = 0 ORDER BY player_no";
        $dbResults = self::getCollectionFromDB($sql);
        return array_map(function($dbResult) { return intval($dbResult['player_id']); }, array_values($dbResults));
    }

    function getOtherPlayersIds(int $playerId) {
        $sql = "SELECT player_id FROM player WHERE player_id <> $playerId AND player_eliminated = 0 AND player_dead = 0 ORDER BY player_no";
        $dbResults = self::getCollectionFromDB($sql);
        return array_map(function($dbResult) { return intval($dbResult['player_id']); }, array_values($dbResults));
    }  

    function getNonZombiePlayersIds() {
        $sql = "SELECT player_id FROM player WHERE player_eliminated = 0 AND player_dead = 0 AND player_zombie = 0 ORDER BY player_no";
        $dbResults = self::getCollectionFromDB($sql);
        return array_map(function($dbResult) { return intval($dbResult['player_id']); }, array_values($dbResults));
    }

    function getPlayer(int $id) {
        $sql = "SELECT * FROM player WHERE player_id = $id";
        $dbResults = self::getCollectionFromDB($sql);
        return array_map(function($dbResult) { return new Player($dbResult); }, array_values($dbResults))[0];
    }

    // get players    
    function getPlayers(bool $includeEliminated = false) {
        $sql = "SELECT * FROM player";
        if (!$includeEliminated) {
            $sql .= " WHERE player_eliminated = 0 AND player_dead = 0";
        }
        $sql .= " ORDER BY player_no";
        $dbResults = self::getCollectionFromDB($sql);
        return array_map(function($dbResult) { return new Player($dbResult); }, array_values($dbResults));
    }

    // get other players    
    function getOtherPlayers(int $playerId, bool $includeEliminated = false) {
        $sql = "SELECT * FROM player WHERE player_id <> $playerId";
        if (!$includeEliminated) {
            $sql .= " AND player_eliminated = 0 AND player_dead = 0";
        }
        $sql .= " ORDER BY player_no";
        $dbResults = self::getCollectionFromDB($sql);
        return array_map(function($dbResult) { return new Player($dbResult); }, array_values($dbResults));
    }

    function getOrderedPlayers(int $currentTurnPlayerId, bool $includeEliminated = false) {
        $players = $this->getPlayers($includeEliminated);

        $playerIndex = 0; 
        foreach($players as $player) {
            if ($player->id == $currentTurnPlayerId) {
                break;
            }
            $playerIndex++;
        }

        $orderedPlayers = $players;
        if ($playerIndex > 0) { // we start from $currentTurnPlayerId and then follow order
            $orderedPlayers = array_merge(array_slice($players, $playerIndex), array_slice($players, 0, $playerIndex));
        }

        return $orderedPlayers;
    }
    
    function eliminatePlayers(int $currentTurnPlayerId) {
        $orderedPlayers = $this->getOrderedPlayers($currentTurnPlayerId, false);

        foreach($orderedPlayers as $player) {
            if ($player->health == 0 && !$player->eliminated) {
                $countZombie = $this->countCardOfType($player->id, ZOMBIE_CARD);
                if ($countZombie == 0) {
                    $this->eliminateAPlayer($player, $currentTurnPlayerId);
                }
            }
        }
    }

    function eliminateAPlayer(object $player, int $currentTurnPlayerId) {
        if ($this->isKingKongExpansion()) {
            // Tokyo Tower levels go back to the table
            $levels = $this->getTokyoTowerLevels($player->id);
            foreach($levels as $level) {
                $this->changeTokyoTowerOwner(0, $level);
            }
        }

        $state = $this->gamestate->state();

        // if player is killing himself
        // in a game state, we can kill him, but else we have to wait the end of his turn
        $playerIsActivePlayer = in_array($player->id, $this->gamestate->getActivePlayerList());
        if ($player->id == $currentTurnPlayerId || $playerIsActivePlayer) {
            $this->asyncEliminatePlayer($player->id);
        } else {
            $scoreAux = intval(self::getGameStateValue(KILL_PLAYERS_SCORE_AUX)); 
            self::DbQuery("UPDATE player SET `player_health` = 0, `player_score` = 0, `player_score_aux` = $scoreAux, player_location = 0 where `player_id` = $player->id");
            self::eliminatePlayer($player->id); // no need for notif, framework does it
        }

        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $player->id));
        $this->removeCards($player->id, $cards, true);
        
        // if player is playing in multipleactiveplayer
        if ($playerIsActivePlayer) {
            $this->gamestate->setPlayerNonMultiactive($player->id, 'stay');
        }        

        if (!$this->isTokyoEmpty(true) && !$this->tokyoBayUsed()) { // 5 players to 4, Tokyo Bay got a player but it shouldn't, so player is moved
            if ($this->isTokyoEmpty(false)) {
                $this->moveFromTokyoBayToCity($this->getPlayerIdInTokyoBay());
            } else {
                $this->leaveTokyo($this->getPlayerIdInTokyoBay());
            }
        }
    }

    function removePlayerFromSmashedPlayersInTokyo(int $playerId) {
        $playersIds = $this->getGlobalVariable(SMASHED_PLAYERS_IN_TOKYO, true);

        if ($playersIds != null && in_array($playerId, $playersIds)) {
            $playersIds = array_filter($playersIds, function ($id) use ($playerId) { return $id != $playerId; });

            $this->setGlobalVariable(SMASHED_PLAYERS_IN_TOKYO, $playersIds);
        }
    }

    // $cardType = 0 => notification with no message
    // $cardType = -1 => no notification

    function applyGetPoints(int $playerId, int $points, int $cardType) {
        if (!$this->canGainPoints($playerId)) {
            return;
        }

        $newPoints = $points;

        $this->applyGetPointsIgnoreCards($playerId, $points, $cardType);

        if ($cardType != ASTRONAUT_CARD) { // to avoid infinite loop
            // Astronaut
            $this->applyAstronaut($playerId);
        }
    }

    function applyGetPointsIgnoreCards(int $playerId, int $points, int $cardType) {
        $actualScore = $this->getPlayerScore($playerId);
        $newScore = min(MAX_POINT, $actualScore + $points);
        self::DbQuery("UPDATE player SET `player_score` = $newScore where `player_id` = $playerId");

        if ($cardType >= 0) {
            $message = $cardType == 0 ? '' : clienttranslate('${player_name} gains ${delta_points} [Star] with ${card_name}');
            self::notifyAllPlayers('points', $message, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'points' => $newScore,
                'delta_points' => $points,
                'card_name' => $cardType == 0 ? null : $cardType,
            ]);
        }
    }

    function applyLosePoints(int $playerId, int $points, int $cardType) {
        $actualScore = $this->getPlayerScore($playerId);
        $newScore = max($actualScore - $points, 0);
        self::DbQuery("UPDATE player SET `player_score` = $newScore where `player_id` = $playerId");

        if ($cardType >= 0) {
            $message = $cardType == 0 ? '' : clienttranslate('${player_name} loses ${delta_points} [Star] with ${card_name}');
            self::notifyAllPlayers('points', $message, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'points' => $newScore,
                'delta_points' => $points,
                'card_name' => $cardType == 0 ? null : $cardType,
            ]);
        }
    }

    function applyGetHealth(int $playerId, int $health, int $cardType, int $healerId) {
        if (!$this->canGainHealth($playerId)) {
            return;
        }

        $this->applyGetHealthIgnoreCards($playerId, $health, $cardType, $healerId);
        
        // regeneration
        $countRegeneration = $this->countCardOfType($playerId, REGENERATION_CARD);
        if ($countRegeneration > 0) {
            $this->applyGetHealthIgnoreCards($playerId, $countRegeneration, REGENERATION_CARD, $playerId);
        }
    }

    function applyGetHealthIgnoreCards(int $playerId, int $health, int $cardType, int $healerId) {
        $maxHealth = $this->getPlayerMaxHealth($playerId);

        $actualHealth = $this->getPlayerHealth($playerId);
        $newHealth = min($actualHealth + $health, $maxHealth);

        if ($actualHealth == $newHealth) {
            return; // already at full life, no need for notif
        }

        self::DbQuery("UPDATE player SET `player_health` = $newHealth where `player_id` = $playerId");

        self::incStat($health, 'heal', $playerId);

        if ($cardType >= 0) {
            $message = $cardType == 0 ? '' : clienttranslate('${player_name} gains ${delta_health} [Heart] with ${card_name}');
            self::notifyAllPlayers('health', $message, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'health' => $newHealth,
                'delta_health' => $health,
                'card_name' => $cardType == 0 ? null : $cardType,
            ]);
        }

        if ($this->isCybertoothExpansion() && $this->isPlayerBerserk($playerId)) {
            $this->setPlayerBerserk($playerId, false);
        }
    }

    private function logDamageBlocked(int $playerId, int $cardType) {
        self::notifyAllPlayers('damageBlockedLog', clienttranslate('${player_name} prevents damage with ${card_name}'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'card_name' => $cardType,
        ]);
    }

    function applyDamage(int $playerId, int $health, int $damageDealerId, int $cardType, int $activePlayerId, int $giveShrinkRayToken, int $givePoisonSpitToken) {
        $canLoseHealth = $this->canLoseHealth($playerId);
        if ($canLoseHealth !== null) {
            $this->removePlayerFromSmashedPlayersInTokyo($playerId);

            $this->logDamageBlocked($playerId, $canLoseHealth);
            return; // player has golden scarab and cannot lose hearts
        }

        if ($this->isInvincible($playerId)) { // TODO move to canLoseHealth ?
            $this->removePlayerFromSmashedPlayersInTokyo($playerId);

            $this->logDamageBlocked($playerId, WINGS_CARD);
            return; // player has wings and cannot lose hearts
        }

        // Armor plating
        $countArmorPlating = $this->countCardOfType($playerId, ARMOR_PLATING_CARD);
        if ($countArmorPlating > 0 && $health == 1) { // TODO move to canLoseHealth ?
            $this->removePlayerFromSmashedPlayersInTokyo($playerId);

            $this->logDamageBlocked($playerId, ARMOR_PLATING_CARD);
            return;
        }

        $actualHealth = $this->getPlayerHealth($playerId);

        $newHealth = $this->applyDamageIgnoreCards($playerId, $health, $damageDealerId, $cardType, $activePlayerId, $giveShrinkRayToken, $givePoisonSpitToken);

        if ($newHealth == 0 && $actualHealth > 0) {
            // eater of the dead 
            $playersIds = $this->getPlayersIds();
            foreach($playersIds as $pId) {
                $countEaterOfTheDead = $this->countCardOfType($pId, EATER_OF_THE_DEAD_CARD);
                if ($countEaterOfTheDead > 0) {
                    $this->applyGetPoints($pId, 3 * $countEaterOfTheDead, EATER_OF_THE_DEAD_CARD);
                }
            }
        }

        if ($health >= 2) {
            // we're only making it stronger          
            $countWereOnlyMakingItStronger = $this->countCardOfType($playerId, WE_RE_ONLY_MAKING_IT_STRONGER_CARD);
            if ($countWereOnlyMakingItStronger > 0) {
                $this->applyGetEnergy($playerId, $countWereOnlyMakingItStronger, WE_RE_ONLY_MAKING_IT_STRONGER_CARD);
            }
        }
    }

    function applyDamageIgnoreCards(int $playerId, int $health, int $damageDealerId, int $cardType, int $activePlayerId, int $giveShrinkRayToken, int $givePoisonSpitToken) {
        if ($this->isInvincible($playerId)) {
            $this->removePlayerFromSmashedPlayersInTokyo($playerId);
            return; // player has wings and cannot lose hearts
        }

        // devil
        $devil = $activePlayerId == $damageDealerId && $playerId != $damageDealerId && $this->countCardOfType($damageDealerId, DEVIL_CARD) > 0;
        if ($devil) {
            $health++;
        }

        $actualHealth = $this->getPlayerHealth($playerId);
        $newHealth = max($actualHealth - $health, 0);

        self::DbQuery("UPDATE player SET `player_health` = $newHealth where `player_id` = $playerId");

        if ($damageDealerId > 0) {
            self::incStat($health, 'damageDealt', $damageDealerId);
        }
        self::incStat($health, 'damage', $playerId);

        if ($cardType >= 0) {
            $message = $cardType == 0 ? '' : clienttranslate('${player_name} loses ${delta_health} [Heart] with ${card_name}');
            self::notifyAllPlayers('health', $message, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'health' => $newHealth,
                'delta_health' => $health,
                'card_name' => $cardType == 0 ? null : $cardType,
            ]);
        }

        if ($devil) {
            self::notifyAllPlayers('devilExtraDamage', clienttranslate('${player_name} loses ${delta_health} [Heart] with ${card_name}'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'delta_health' => 1,
                'card_name' => DEVIL_CARD,
            ]);
        }

        // Shrink Ray
        if ($giveShrinkRayToken > 0) {
            $this->applyGetShrinkRayToken($playerId, $giveShrinkRayToken);
        }

        // Poison Spit
        if ($givePoisonSpitToken > 0) {
            $this->applyGetPoisonToken($playerId, $givePoisonSpitToken);
        }
            
        self::DbQuery("INSERT INTO `turn_damages`(`from`, `to`, `damages`)  VALUES ($damageDealerId, $playerId, $health) ON DUPLICATE KEY UPDATE `damages` = `damages` + $health");

        // pirate
        $pirateCards = $this->getCardsOfType($damageDealerId, PIRATE_CARD);
        if (count($pirateCards) > 0 && $this->getPlayerEnergy($playerId) >= 1) {
            $this->applyLoseEnergy($playerId, 1, PIRATE_CARD);
            $this->applyGetEnergy($damageDealerId, 1, PIRATE_CARD);
        }

        // must be done before player eliminations
        if ($this->countCardOfType($playerId, IT_HAS_A_CHILD_CARD) > 0 && $this->getPlayerHealth($playerId) == 0) {
            // it has a child
            $this->applyItHasAChild($playerId);
            // TODO make notifs for this happen after dice notifs
        }

        $this->eliminatePlayers($activePlayerId);

        return $newHealth;
    }

    function applyGetEnergy(int $playerId, int $energy, int $cardType) {
        if (!$this->canGainEnergy($playerId)) {
            return;
        }

        $this->applyGetEnergyIgnoreCards($playerId, $energy, $cardType);

        // friend of children
        $countFriendOfChildren = $this->countCardOfType($playerId, FRIEND_OF_CHILDREN_CARD);
        if ($countFriendOfChildren > 0) {
            $this->applyGetEnergyIgnoreCards($playerId, $countFriendOfChildren, FRIEND_OF_CHILDREN_CARD);
        }
    }

    function applyGetEnergyIgnoreCards(int $playerId, int $energy, int $cardType) {
        self::DbQuery("UPDATE player SET `player_energy` = `player_energy` + $energy where `player_id` = $playerId");

        if ($cardType >= 0) {
            $message = $cardType == 0 ? '' : clienttranslate('${player_name} gains ${delta_energy} [Energy] with ${card_name}');
            self::notifyAllPlayers('energy', $message, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'energy' => $this->getPlayerEnergy($playerId),
                'delta_energy' => $energy,
                'card_name' => $cardType == 0 ? null : $cardType,
            ]);
        }

        self::incStat($energy, 'wonEnergyCubes', $playerId);
    }

    function applyLoseEnergy(int $playerId, int $energy, int $cardType) {
        $this->applyLoseEnergyIgnoreCards($playerId, $energy, $cardType);
    }

    function applyLoseEnergyIgnoreCards(int $playerId, int $energy, int $cardType) {
        $actualEnergy = $this->getPlayerEnergy($playerId);
        $newEnergy = max($actualEnergy - $energy, 0);
        self::DbQuery("UPDATE player SET `player_energy` = $newEnergy where `player_id` = $playerId");

        if ($cardType >= 0) {
            $message = $cardType == 0 ? '' : clienttranslate('${player_name} loses ${delta_energy} [Energy] with ${card_name}');
            self::notifyAllPlayers('energy', $message, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'energy' => $newEnergy,
                'delta_energy' => $energy,
                'card_name' => $cardType == 0 ? null : $cardType,
            ]);
        }
    }

    function applyGetShrinkRayToken(int $playerId, int $deltaTokens) {
        self::DbQuery("UPDATE player SET `player_shrink_ray_tokens` = `player_shrink_ray_tokens` + $deltaTokens where `player_id` = $playerId");

        $message = clienttranslate('${player_name} gets ${delta_tokens} Shrink Ray token with ${card_name}');
        self::notifyAllPlayers('shrinkRayToken', $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'delta_tokens' => $deltaTokens,
            'card_name' => SHRINK_RAY_CARD,
            'tokens' => $this->getPlayerShrinkRayTokens($playerId),
        ]);
    }

    function applyGetPoisonToken(int $playerId, int $deltaTokens) {
        self::DbQuery("UPDATE player SET `player_poison_tokens` = `player_poison_tokens` + $deltaTokens where `player_id` = $playerId");

        $message = clienttranslate('${player_name} gets ${delta_tokens} Poison token with ${card_name}');
        self::notifyAllPlayers('poisonToken', $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'delta_tokens' => $deltaTokens,
            'card_name' => POISON_SPIT_CARD,
            'tokens' => $this->getPlayerPoisonTokens($playerId),
        ]);
    }

    function removeShrinkRayToken(int $playerId, int $deltaTokens = 1) {
        $actualTokens = $this->getPlayerShrinkRayTokens($playerId);
        $newTokens = max($actualTokens - $deltaTokens, 0);

        self::DbQuery("UPDATE player SET `player_shrink_ray_tokens` = $newTokens where `player_id` = $playerId");

        self::notifyAllPlayers('removeShrinkRayToken', '', [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'deltaTokens' => $deltaTokens,
            'tokens' => $newTokens,
        ]);
    }
    

    function removePoisonToken(int $playerId, int $deltaTokens = 1) {
        $actualTokens = $this->getPlayerPoisonTokens($playerId);
        $newTokens = max($actualTokens - $deltaTokens, 0);

        self::DbQuery("UPDATE player SET `player_poison_tokens` = $newTokens where `player_id` = $playerId");

        self::notifyAllPlayers('removePoisonToken', '', [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'deltaTokens' => $deltaTokens,
            'tokens' => $newTokens,
        ]);
    }

    function resolveDamages(array $damages, /* string|int|function */ $endStateOrTransition) { // bool redirect to cancelDamage
        if ($endStateOrTransition == null || (gettype($endStateOrTransition) != 'string' && gettype($endStateOrTransition) != 'integer')) {
            throw new \Error('resolveDamages : endStateOrTransition wrong '); 
        }

        $cancellableDamages = [];
        $playersIds = [];
        foreach ($damages as $damage) {
            if (CancelDamageIntervention::canDoIntervention($this, $damage->playerId, $damage->damage)) {
                $cancellableDamages[] = $damage;
                if (!in_array($damage->playerId, $playersIds)) {
                    $playersIds[] = $damage->playerId;
                }
            } else {
                $activePlayerId = self::getActivePlayerId();
                $this->applyDamage($damage->playerId, $damage->damage, $damage->damageDealerId, $damage->cardType, $activePlayerId, $damage->giveShrinkRayToken, $damage->givePoisonSpitToken);
            }
        }

        if (count($cancellableDamages) > 0) {
            $cancelDamageIntervention = new CancelDamageIntervention($playersIds, $cancellableDamages);
            $cancelDamageIntervention->endState = $endStateOrTransition;
            $this->setGlobalVariable(CANCEL_DAMAGE_INTERVENTION, $cancelDamageIntervention);
            $this->jumpToState(ST_MULTIPLAYER_CANCEL_DAMAGE);

            return true;
        } else {
            return false;
        }
    }

    function jumpToState(int $stateId) {
        $state = $this->gamestate->state();
        // we redirect only if game is not ended, and player is still active (not redirected to next player)
        if ($state['name'] != 'gameEnd') {
            // TODOAN $isEliminated = $this->getPlayer($playerId)->eliminated;
            $this->gamestate->jumpToState(/*$isEliminated ? ST_NEXT_PLAYER :*/ $stateId);
        }
    }

    function updateKillPlayersScoreAux() {
        $eliminatedPlayersCount = intval(self::getUniqueValueFromDB("select count(*) from player where player_eliminated > 0 or player_dead > 0"));
        self::setGameStateValue(KILL_PLAYERS_SCORE_AUX, $eliminatedPlayersCount + 1);
    }

    function isDamageTakenThisTurn(int $playerId) {
        return intval(self::getUniqueValueFromDB( "SELECT SUM(`damages`) FROM `turn_damages` WHERE `to` = $playerId")) > 0;
    }

    function isDamageDealtThisTurn(int $playerId) {
        return intval(self::getUniqueValueFromDB( "SELECT SUM(`damages`) FROM `turn_damages` WHERE `from` = $playerId")) > 0;
    }

    function isDamageDealtToOthersThisTurn(int $playerId) {
        return intval(self::getUniqueValueFromDB( "SELECT SUM(`damages`) FROM `turn_damages` WHERE `from` = $playerId and `to` <> $playerId")) > 0;
    }

    function playersWoundedByActivePlayerThisTurn(int $playerId) {
        $dbResults = self::getCollectionFromDB("SELECT `to` FROM `turn_damages` WHERE `from` = $playerId");
        return array_map(function($dbResult) { return intval($dbResult['to']); }, array_values($dbResults));
    }

    function placeNewCardsOnTable() {
        $cards = [];
        
        for ($i=1; $i<=3; $i++) {
            $cards[] = $this->getCardFromDb($this->cards->pickCardForLocation('deck', 'table', $i));
        }

        return $cards;
    }

    function getTokyoTowerLevels(int $playerId) {
        $dbResults = self::getCollectionFromDB("SELECT `level` FROM `tokyo_tower` WHERE `owner` = $playerId order by `level`");
        return array_map(function($dbResult) { return intval($dbResult['level']); }, array_values($dbResults));
    }

    function changeTokyoTowerOwner(int $playerId, int $level) {
        self::DbQuery("UPDATE `tokyo_tower` SET  `owner` = $playerId where `level` = $level");

        $message = $playerId == 0 ? '' : /* client TODOKK translate(*/'${player_name} claims Tokyo Tower level ${level}'/*)*/;
        self::notifyAllPlayers("changeTokyoTowerOwner", $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'level' => $level,
        ]);
    }

    function getPlayersIdsWithMaxColumn(string $column) {
        $sql = "SELECT player_id FROM player WHERE player_eliminated = 0 AND player_dead = 0 AND `$column` = (select max(`$column`) FROM player WHERE player_eliminated = 0 AND player_dead = 0) ORDER BY player_no";
        $dbResults = self::getCollectionFromDB($sql);
        return array_map(function($dbResult) { return intval($dbResult['player_id']); }, array_values($dbResults));
    }
}
